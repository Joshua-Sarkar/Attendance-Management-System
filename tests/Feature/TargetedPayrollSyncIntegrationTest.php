<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\EmployeeProfile;
use App\Models\PayrollAuditLog;
use App\Services\PayrollService;
use App\Services\NumberToWordsFormatter;
use App\Events\AttendanceOverridden;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TargetedPayrollSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $cycle;

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
            'name' => 'Arjun Bisht',
            'email' => 'arjun@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        $this->employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        // Setup the cycle
        $this->cycle = PayrollCycle::create([
            'period' => 'June 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);
    }

    /** @test */
    public function full_sequence_salary_edit_sync_override_recalc_approval_locking()
    {
        // 1. Initial process/population
        PayrollService::processCycle('June 2026', $this->admin);

        $record = PayrollRecord::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(50000.00, $record->base_salary);
        $this->assertEquals(1, $record->calculation_version);

        // 2. Admin Salary Update Sync Trigger
        $this->actingAs($this->admin);
        PayrollService::updateProfile($this->employee, [
            'base_salary' => 60000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        // Verify record recalculated automatically
        $record->refresh();
        $this->assertEquals(60000.00, $record->base_salary);
        $this->assertEquals(2, $record->calculation_version);
        $this->assertEquals('stale', $record->employee_review_status);

        // Verify Audit Log generated
        $this->assertTrue(
            PayrollAuditLog::where('user_id', $this->employee->id)
                ->where('category', 'Salary Change')
                ->exists()
        );

        // 3. Ledger Override Trigger (Attendance Mutation)
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-15',
            'check_in_time' => '2026-06-15 09:30:00',
            'check_out_time' => '2026-06-15 17:30:00',
            'status' => 'present',
            'classification' => 'full_day',
        ]);

        // Dispatch AttendanceOverridden event
        event(new AttendanceOverridden(
            $this->employee,
            Carbon::parse('2026-06-15'),
            $this->admin
        ));

        // Verify version increased due to recalculation trigger
        $record->refresh();
        $this->assertEquals(3, $record->calculation_version);

        // 4. Employee Approval
        $record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
        ]);

        // 5. Admin Approval
        $record->update([
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $this->admin->id,
        ]);

        // Run lock check
        PayrollService::lockRecord($record, $this->admin);

        $record->refresh();
        $this->assertTrue((bool)$record->locked);
        $this->assertEquals('locked', $record->status);

        // 6. Verification of Locked Status preventing mutation
        $currentFingerprint = $record->fingerprint;

        // Try to trigger recalculation on locked record
        PayrollService::recalculateRecord($record, $this->admin);

        $record->refresh();
        $this->assertEquals($currentFingerprint, $record->fingerprint);
        $this->assertEquals(3, $record->calculation_version);

        // 7. Verify spelling/currency format spellout falls back safely
        $words = NumberToWordsFormatter::convert(12138);
        $this->assertStringContainsString('TWELVE THOUSAND ONE HUNDRED THIRTY-EIGHT', strtoupper($words));
    }

    /** @test */
    public function attendance_deduction_breakdown_returns_granular_quantities_rates_and_dates()
    {
        PayrollService::processCycle('June 2026', $this->admin);
        $record = PayrollRecord::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($record);

        // Add daily breakdown with half day and unpaid leave entries
        $meta = $record->calculation_metadata ?? [];
        $meta['daily_rate'] = 2000.00;
        $meta['daily_breakdown'] = [
            '2026-06-10' => ['status' => 'half', 'deducted_amount' => 1000.00, 'is_override' => true, 'notes' => 'Half Day Override'],
            '2026-06-18' => ['status' => 'upa', 'deducted_amount' => 2000.00, 'is_override' => false, 'notes' => 'Unpaid Leave'],
        ];
        $record->update([
            'calculation_metadata' => $meta,
            'half_days' => 1,
            'unpaid_leave_days' => 1,
            'attendance_deductions' => 3000.00,
        ]);

        $breakdown = PayrollService::getAttendanceDeductionBreakdown($record->refresh());

        $this->assertEquals(3000.00, $breakdown['total_deductions']);
        $this->assertEquals(1, $breakdown['half_days']['quantity']);
        $this->assertEquals(1000.00, $breakdown['half_days']['rate']);
        $this->assertEquals(1000.00, $breakdown['half_days']['amount']);
        $this->assertContains('10 Jun 2026', $breakdown['half_days']['dates']);

        $this->assertEquals(1, $breakdown['unpaid_leaves']['quantity']);
        $this->assertEquals(2000.00, $breakdown['unpaid_leaves']['rate']);
        $this->assertEquals(2000.00, $breakdown['unpaid_leaves']['amount']);
        $this->assertContains('18 Jun 2026', $breakdown['unpaid_leaves']['dates']);

        $this->assertCount(2, $breakdown['itemized_dates']);
    }
}
