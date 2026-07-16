<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Services\PayrollService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ControlledReopenAndSyncValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $cycle;
    protected $record;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
        ]);

        $this->employee = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Software Engineer',
            'employee_category' => 'Permanent',
        ]);

        $this->employee->payrollProfile()->create([
            'base_salary' => 45000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        $this->cycle = PayrollCycle::create([
            'period' => 'June 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        // Process initially
        PayrollService::processCycle('June 2026', $this->admin);
        $this->record = PayrollRecord::where('user_id', $this->employee->id)->firstOrFail();
    }

    /** @test */
    public function reopen_locked_record_resets_approvals_revokes_payslip_and_stores_history()
    {
        // 1. Approve and lock the record first
        $this->record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now()->subMinutes(10),
            'admin_approved_at' => now()->subMinutes(5),
            'admin_approved_by_id' => $this->admin->id,
            'payslip_status' => 'published',
        ]);

        PayrollService::lockRecord($this->record, $this->admin);
        $this->record->refresh();
        $this->assertTrue((bool)$this->record->locked);
        $this->assertEquals('locked', $this->record->status);

        // 2. Perform controlled administrative reopen
        PayrollService::reopenRecord($this->record, 'Correcting unpaid absence mismatch', $this->admin);
        $this->record->refresh();

        // 3. Verify approvals reset, lock removed, version incremented, payslip revoked
        $this->assertFalse((bool)$this->record->locked);
        $this->assertEquals('pending', $this->record->status);
        $this->assertEquals('pending', $this->record->employee_review_status);
        $this->assertNull($this->record->employee_approved_at);
        $this->assertNull($this->record->admin_approved_at);
        $this->assertEquals('revoked', $this->record->payslip_status);
        $this->assertEquals(2, $this->record->calculation_version);

        // 4. Verify historical trace is logged in metadata
        $metadata = $this->record->calculation_metadata;
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('reopen_history', $metadata);
        $this->assertCount(1, $metadata['reopen_history']);
        
        $history = $metadata['reopen_history'][0];
        $this->assertEquals('Correcting unpaid absence mismatch', $history['reason']);
        $this->assertEquals(1, $history['old_version']);
        $this->assertEquals($this->admin->id, $history['reopened_by_id']);
        $this->assertEquals('generated', $history['old_payslip_status']);
    }

    /** @test */
    public function cannot_modify_salary_profile_if_overlapping_locked_payroll()
    {
        // 1. Approve and lock the record
        $this->record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $this->admin->id,
        ]);
        PayrollService::lockRecord($this->record, $this->admin);

        // 2. Attempt to update salary profile effective date during locked period
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot modify salary. A locked payroll record exists for this employee after or overlapping the effective date.');

        PayrollService::updateProfile($this->employee, [
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-06-15', // overlaps locked June 2026 cycle
            'payroll_enabled' => true,
        ]);
    }

    /** @test */
    public function cannot_apply_for_leave_overlapping_locked_payroll()
    {
        // 1. Approve and lock the record
        $this->record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $this->admin->id,
        ]);
        PayrollService::lockRecord($this->record, $this->admin);

        // 2. Attempt to submit/apply for leave request inside June 2026
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot modify leave request. Payroll is locked and immutable for this period.');

        LeaveBalanceService::applyRequest($this->employee, [
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'leave_type' => 'planned',
            'is_half_day' => false,
            'reason' => 'Family event',
        ]);
    }

    /** @test */
    public function admin_reopen_endpoint_is_secured_and_accessible_only_by_admins()
    {
        // 1. Approve and lock record
        $this->record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $this->admin->id,
        ]);
        PayrollService::lockRecord($this->record, $this->admin);

        // 2. Try to reopen as employee - must get 403 Forbidden
        $this->actingAs($this->employee);
        $response = $this->postJson(route('admin.payroll.records.reopen', $this->record->id), [
            'reason' => 'Unauthorised user reopen request'
        ]);
        $response->assertStatus(403);

        // 3. Reopen as admin - must succeed
        $this->actingAs($this->admin);
        $response = $this->postJson(route('admin.payroll.records.reopen', $this->record->id), [
            'reason' => 'Authorised admin correction'
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Record reopened and recalculated successfully.'
        ]);

        $this->record->refresh();
        $this->assertFalse((bool)$this->record->locked);
    }
}
