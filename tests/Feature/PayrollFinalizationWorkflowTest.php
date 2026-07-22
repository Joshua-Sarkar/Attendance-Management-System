<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\EmployeeProfile;
use App\Models\PayrollException;
use App\Models\PayrollDispute;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollFinalizationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee1;
    protected $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-01',
        ]);

        $this->employee1 = User::factory()->create([
            'name' => 'Arjun Bisht',
            'email' => 'arjun@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee1->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        $this->employee1->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);

        $this->employee2 = User::factory()->create([
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee2->id,
            'designation' => 'QA Analyst',
            'employee_category' => 'Permanent',
        ]);

        $this->employee2->payrollProfile()->create([
            'base_salary' => 45000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);
    }

    /** @test */
    public function initial_cycle_is_generated_and_employee_approvals_sync_status_to_ready_to_lock()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        $this->assertEquals('generated', $cycle->status);
        $this->assertEquals(2, $cycle->payrollRecords()->count());

        $record1 = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $this->employee1->id)->first();
        $record2 = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $this->employee2->id)->first();

        // Employee 1 approves
        $this->actingAs($this->employee1);
        PayrollService::approveEmployeeRecord($record1, $this->employee1);

        $cycle->refresh();
        $this->assertEquals('generated', $cycle->status);

        // Employee 2 approves
        $this->actingAs($this->employee2);
        PayrollService::approveEmployeeRecord($record2, $this->employee2);

        $cycle->refresh();
        $this->assertEquals('ready_to_lock', $cycle->status);
    }

    /** @test */
    public function lock_is_prevented_when_unresolved_critical_exceptions_exist()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        PayrollException::create([
            'payroll_cycle_id' => $cycle->id,
            'user_id' => $this->employee1->id,
            'type' => 'Missing Base Salary',
            'severity' => 'Critical',
            'error_code' => 'MISSING_BASE_SALARY',
            'description' => 'Critical salary configuration missing',
            'resolved' => false,
        ]);

        $result = PayrollService::lockCycle($cycle, $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('unresolved critical exception', $result['message']);
        $this->assertEquals('corrections_pending', $cycle->fresh()->status);
    }

    /** @test */
    public function lock_is_prevented_when_open_disputes_exist()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $this->employee1->id)->first();

        PayrollService::disputeEmployeeRecord($record, $this->employee1, [
            'category' => 'Attendance',
            'description' => 'Missing present day on June 10',
            'expected_correction' => 'Mark present',
        ]);

        $result = PayrollService::lockCycle($cycle, $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('open employee dispute', $result['message']);
    }

    /** @test */
    public function admin_lock_transactionally_seals_cycle_and_releases_payslips()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        // All approve
        foreach ($cycle->payrollRecords as $rec) {
            PayrollService::approveEmployeeRecord($rec, $rec->user);
        }

        $result = PayrollService::lockCycle($cycle, $this->admin);

        $this->assertTrue($result['success']);

        $cycle->refresh();
        $this->assertEquals('locked', $cycle->status);
        $this->assertNotNull($cycle->locked_at);
        $this->assertEquals($this->admin->id, $cycle->locked_by_id);

        foreach ($cycle->payrollRecords as $rec) {
            $rec->refresh();
            $this->assertTrue($rec->locked);
            $this->assertEquals('locked', $rec->status);
            $this->assertEquals('published', $rec->payslip_status);
            $this->assertNotNull($rec->locked_snapshot);
        }
    }

    /** @test */
    public function locked_payroll_is_immutable_and_cannot_be_recalculated()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        PayrollService::lockCycle($cycle, $this->admin);

        // Attempting to process cycle again
        $reprocessed = PayrollService::processCycle('June 2026', $this->admin);
        $this->assertEquals('locked', $reprocessed->status);

        // Attempting via controller
        $this->actingAs($this->admin);
        $response = $this->post(route('admin.payroll.process'), ['period' => 'June 2026']);
        $response->assertSessionHas('error');
    }

    /** @test */
    public function payslip_download_restriction_before_and_after_lock()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $this->employee1->id)->first();

        // 1. Download BEFORE lock -> Aborts with 400
        $this->actingAs($this->employee1);
        $responseBefore = $this->get(route('employee.payslip.download', ['id' => $record->id]));
        $responseBefore->assertStatus(400);

        // 2. Lock cycle
        PayrollService::lockCycle($cycle, $this->admin);

        // 3. Download AFTER lock -> Returns PDF stream (HTTP 200)
        $responseAfter = $this->get(route('employee.payslip.download', ['id' => $record->id]));
        $responseAfter->assertStatus(200);
        $responseAfter->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function per_record_admin_approval_and_lock_transitions_payslip_status_to_published()
    {
        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $this->employee1->id)->first();

        // 1. Employee approves
        PayrollService::approveEmployeeRecord($record, $this->employee1);

        // 2. Admin approves and locks record
        $this->actingAs($this->admin);
        $response = $this->post(route('admin.payroll.records.approve', ['id' => $record->id]));
        $response->assertStatus(200);

        $record->refresh();
        $this->assertTrue($record->locked);
        $this->assertEquals('published', $record->payslip_status);

        // 3. Employee downloads payslip -> Succeeds with 200 PDF
        $this->actingAs($this->employee1);
        $downloadResponse = $this->get(route('employee.payslip.download', ['id' => $record->id]));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/pdf');
    }
}
