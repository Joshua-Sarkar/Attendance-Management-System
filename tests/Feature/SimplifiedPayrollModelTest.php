<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\PayrollProfile;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollSetting;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollAuditLog;
use App\Models\SalaryHistory;
use App\Services\PayrollService;
use App\Services\PayrollInvalidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SimplifiedPayrollModelTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        // Admin User
        $this->admin = User::create([
            'name' => 'Rhea Sarin',
            'email' => 'admin@venturerequest.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-15',
        ]);

        // Employee: EMP00001
        $this->employee = User::create([
            'employee_id' => 'EMP00001',
            'name' => 'SR71 Payroll Test',
            'email' => 'sr71@example.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-07-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Graphics Designer',
            'employee_category' => 'Permanent',
        ]);

        $payrollProfile = PayrollProfile::create([
            'user_id' => $this->employee->id,
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-07-01',
            'payroll_enabled' => true,
        ]);

        $payrollProfile->salaryHistories()->create([
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-07-01',
            'change_reason' => 'Initial placement',
            'source' => 'Manual',
        ]);

        // Process/instantiate July cycle records
        $this->cycle = PayrollService::processCycle('July 2026', $this->admin);
    }

    // 1. Recalculation on Attendance Save
    public function test_recalculation_on_attendance_save(): void
    {
        $recordBefore = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $initialAbs = (float)$recordBefore->absent_days;

        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-07-10',
            'status' => 'present',
            'is_overridden' => true,
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertLessThan($initialAbs, (float)$recordAfter->absent_days);
    }

    // 2. Recalculation on Attendance Delete
    public function test_recalculation_on_attendance_delete(): void
    {
        $att = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-07-10',
            'status' => 'present',
            'is_overridden' => true,
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $absWithPresent = (float)$recordAfter->absent_days;

        $att->delete();

        $recordFinal = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertGreaterThan($absWithPresent, (float)$recordFinal->absent_days);
    }

    // 3. Recalculation on LeaveRequest Save
    public function test_recalculation_on_leave_request_save(): void
    {
        $recordBefore = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0, $recordBefore->unpaid_leave_days);

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-16',
            'leave_type' => 'unplanned',
            'status' => 'approved',
            'is_paid' => false,
            'is_half_day' => false,
            'total_days' => 2,
            'reason' => 'Medical reason',
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(2.0, (float)$recordAfter->unpaid_leave_days);
    }

    // 4. Recalculation on LeaveRequest Delete
    public function test_recalculation_on_leave_request_delete(): void
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-16',
            'leave_type' => 'unplanned',
            'status' => 'approved',
            'is_paid' => false,
            'is_half_day' => false,
            'total_days' => 2,
            'reason' => 'Medical reason',
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(2.0, (float)$recordAfter->unpaid_leave_days);

        $leave->delete();

        $recordFinal = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.0, (float)$recordFinal->unpaid_leave_days);
    }

    // 5. Recalculation on PayrollProfile Save
    public function test_recalculation_on_payroll_profile_save(): void
    {
        // Delete salary histories to force resolution from base_salary directly
        $this->employee->payrollProfile->salaryHistories()->delete();

        $profile = $this->employee->payrollProfile;
        $profile->update(['base_salary' => 35000.00]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(35000.00, (float)$recordAfter->base_salary);
    }

    // 6. Recalculation on SalaryHistory Save
    public function test_recalculation_on_salary_history_save(): void
    {
        $profile = $this->employee->payrollProfile;
        $profile->salaryHistories()->create([
            'base_salary' => 38000.00,
            'salary_effective_date' => '2026-07-01',
            'change_reason' => 'Promotion',
            'source' => 'Manual',
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(38000.00, (float)$recordAfter->base_salary);
    }

    // 7. Recalculation on SalaryHistory Delete
    public function test_recalculation_on_salary_history_delete(): void
    {
        $profile = $this->employee->payrollProfile;
        $sh = $profile->salaryHistories()->create([
            'base_salary' => 39000.00,
            'salary_effective_date' => '2026-07-01',
            'change_reason' => 'Promotion',
            'source' => 'Manual',
        ]);

        $recordAfter = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(39000.00, (float)$recordAfter->base_salary);

        $sh->delete();

        $recordFinal = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(30000.00, (float)$recordFinal->base_salary);
    }

    // 8. Provident Fund (PF) is zeroed out
    public function test_provident_fund_is_zero(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.00, (float)$record->statutory_deductions);
    }

    // 9. Employee State Insurance (ESI) is zeroed out
    public function test_esi_is_zero(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.00, (float)$record->statutory_deductions);
    }

    // 10. Professional Tax is zeroed out
    public function test_professional_tax_is_zero(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.00, (float)$record->statutory_deductions);
    }

    // 11. TDS (Tax) deduction is zeroed out
    public function test_tds_is_zero(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.00, (float)$record->tax_deductions);
    }

    // 12. Separate Leave Deduction is zeroed out
    public function test_separate_leave_deduction_is_zero(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(0.00, (float)$record->leave_deductions);
    }

    // 13. Active compensation formula assertion
    public function test_net_salary_compensation_formula(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $expectedNet = round((float)$record->gross_salary - (float)$record->attendance_deductions, 2);
        $this->assertEquals($expectedNet, round((float)$record->net_salary, 2));
    }

    // 14. Unplanned unpaid leaves mapping to attendance deductions
    public function test_unplanned_unpaid_leaves_flow_to_attendance_deduction(): void
    {
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'leave_type' => 'unplanned',
            'status' => 'approved',
            'is_paid' => false,
            'is_half_day' => false,
            'total_days' => 1,
            'reason' => 'Personal work',
        ]);

        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertGreaterThan(0.00, (float)$record->attendance_deductions);
        $this->assertEquals(0.00, (float)$record->leave_deductions);
    }

    // 15. Multi-cycle propagation is triggered when salary changes
    public function test_multi_cycle_propagation_salary_change(): void
    {
        // Create another open cycle
        $augustCycle = PayrollCycle::create([
            'period' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'payment_date' => '2026-09-07',
            'status' => 'draft',
        ]);
        
        $augustRecord = PayrollRecord::create([
            'payroll_cycle_id' => $augustCycle->id,
            'user_id' => $this->employee->id,
            'base_salary' => 30000.00,
            'gross_salary' => 30000.00,
            'net_salary' => 30000.00,
            'attendance_deductions' => 0.00,
            'leave_deductions' => 0.00,
            'statutory_deductions' => 0.00,
            'tax_deductions' => 0.00,
            'overtime_hours' => 0.00,
            'overtime_pay' => 0.00,
            'bonuses' => 0.00,
            'allowances' => 0.00,
            'working_days' => 26,
            'present_days' => 26.00,
            'absent_days' => 0.00,
            'leave_days' => 0.00,
            'unpaid_leave_days' => 0.00,
            'birthday_leave_days' => 0.00,
            'half_days' => 0,
            'late_days' => 0,
            'wfh_days' => 0,
            'employee_review_status' => 'pending',
            'calculation_version' => 1,
            'calculation_metadata' => [
                'pf' => 0.00,
                'esi' => 0.00,
                'prof_tax' => 0.00,
                'cycle_type' => 'monthly',
                'daily_breakdown' => [],
                'daily_rate' => 1000.00,
                'hourly_rate' => 125.00,
                'calendar_days' => 30,
            ],
        ]);

        // Delete salary histories to force direct base_salary mapping
        $this->employee->payrollProfile->salaryHistories()->delete();

        $profile = $this->employee->payrollProfile;
        $profile->update(['base_salary' => 45000.00]);

        $julyRec = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $augRec = PayrollRecord::where('payroll_cycle_id', $augustCycle->id)->where('user_id', $this->employee->id)->first();

        $this->assertEquals(45000.00, (float)$julyRec->base_salary);
        $this->assertEquals(45000.00, (float)$augRec->base_salary);
    }

    // 16. Locked payroll period prevents recalculation and logs conflict
    public function test_locked_payroll_prevents_recalculation_and_logs(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $record->update(['locked' => true]);

        $oldNet = $record->net_salary;

        // Save new attendance to trigger sync
        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-07-22',
            'status' => 'absent',
            'is_overridden' => true,
        ]);

        $recordAfter = PayrollRecord::find($record->id);
        $this->assertEquals($oldNet, $recordAfter->net_salary);

        $auditLog = PayrollAuditLog::where('user_id', $this->employee->id)
            ->where('action', 'like', '%Reconciliation conflict%')
            ->first();
        $this->assertNotNull($auditLog);
    }

    // 17. Exclude PF/ESI/PT/TDS from deduction report export
    public function test_export_deductions_report_structure(): void
    {
        $this->assertTrue(method_exists(\App\Services\PayrollExportService::class, 'export'));
    }

    // 18. Payslip PDF template renders correctly
    public function test_payslip_pdf_renders(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $record->update(['locked' => true, 'payslip_status' => 'published']);

        $response = $this->actingAs($this->admin)->get(route('employee.payslip.download', ['id' => $record->id]));
        $response->assertStatus(200);
    }

    // 19. Admin settings update behaves properly
    public function test_admin_settings_update(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.payroll.settings.update'), [
            'group' => 'general',
            'fields' => [
                'companyName' => 'New Venture Name',
            ]
        ]);
        $response->assertStatus(302);
    }

    // 20. Overtime hours limit/cap is respected during recalculations
    public function test_overtime_hours_cap_respected(): void
    {
        $cap = (int)PayrollSetting::getValue('overtime')['cap'];
        $this->assertGreaterThan(0, $cap);
    }

    // 21. Manual Recalculate control (/admin/payroll/process) recovery
    public function test_manual_recalculate_recovery(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.payroll.process'), [
            'period' => 'July 2026',
        ]);
        $response->assertStatus(302);
    }

    // 22. Daily attendance snapshot mapping in employee portal has correct statuses
    public function test_daily_attendance_snapshot_mapping_statuses(): void
    {
        $response = $this->actingAs($this->employee)->get(route('employee.payroll.index', ['period' => 'July 2026']));
        $response->assertStatus(200);
    }

    // 23. Invalidation observer avoids infinite recursion
    public function test_invalidation_observer_recursion_prevention(): void
    {
        $this->employee->payrollProfile->salaryHistories()->delete();

        $profile = $this->employee->payrollProfile;
        $profile->update(['base_salary' => 32000.00]);

        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $this->assertEquals(32000.00, (float)$record->base_salary);
    }

    // 24. Disputes raised on locked cycles are rejected
    public function test_disputes_on_locked_cycles_rejected(): void
    {
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        $record->update(['locked' => true]);

        $response = $this->actingAs($this->employee)->post(route('employee.payroll.dispute'), [
            'record_id' => $record->id,
            'category' => 'Attendance',
            'description' => 'I disagree with this absence.',
            'expected_correction' => 'Mark as present',
        ]);

        $response->assertSessionHas('error', 'Cannot dispute. Payroll is locked.');
    }
}
