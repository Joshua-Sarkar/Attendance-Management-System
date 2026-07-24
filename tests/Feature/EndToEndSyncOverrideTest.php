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
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EndToEndSyncOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-01',
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP20002',
            'name' => 'John Sync',
            'email' => 'john.sync@example.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-07-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'QA Engineer',
            'employee_category' => 'Permanent',
        ]);

        $payrollProfile = PayrollProfile::create([
            'user_id' => $this->employee->id,
            'base_salary' => 31000.00, // 31000 / 31 = 1000 daily rate
            'salary_effective_date' => '2026-07-01',
            'payroll_enabled' => true,
        ]);

        $payrollProfile->salaryHistories()->create([
            'base_salary' => 31000.00,
            'salary_effective_date' => '2026-07-01',
            'change_reason' => 'Initial setup',
            'source' => 'Manual',
        ]);

        // July 2026 cycle
        $this->cycle = PayrollService::processCycle('July 2026', $this->admin);

        // Reset default leave balance to 0 first
        $this->employee->update(['leave_balance' => 0.00]);
        if ($this->employee->leaveBalance) {
            $this->employee->leaveBalance->update(['remaining_leave' => 0.00]);
        }
    }

    /**
     * Test HD-UPR manual override propagation.
     */
    public function test_hd_upr_propagation_and_recalculation(): void
    {
        // 1. Apply HD-UPR override via API/controller
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee->id],
            'date_mode' => 'single',
            'date' => '2026-07-15',
            'status' => 'hd_upr',
            'classification' => 'automatic',
            'override_reason' => 'Testing HD-UPR sync override',
            'conflict_handling' => 'replace',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Applied overrides successfully to 1 record(s).');

        $att = Attendance::where('user_id', $this->employee->id)->where('date', '2026-07-15 00:00:00')->first();
        $this->assertNotNull($att);
        $this->assertEquals('hd_upr', $att->status);
        $this->assertEquals('half_day', $att->classification);

        // Resolve status from AttendanceService: must be canonical 'hd_upr'
        $attendanceService = app(\App\Services\AttendanceService::class);
        $state = $attendanceService->resolveStateForDate($this->employee, Carbon::parse('2026-07-15'), $att);
        $this->assertEquals('hd_upr', $state['status']);
        $this->assertEquals('half_day', $state['classification']);

        // Assert payroll record has recalculated automatically (unlocked)
        $payrollRecord = PayrollRecord::where('user_id', $this->employee->id)->where('payroll_cycle_id', $this->cycle->id)->first();
        $this->assertNotNull($payrollRecord);

        // Verify Daily Breakdown labels mirror the status and deduction state (50% pay deduction = 500)
        $breakdown = $payrollRecord->calculation_metadata['daily_breakdown'];
        $this->assertEquals('hd_upr', $breakdown['2026-07-15']['status']);
        $this->assertEquals('HD-UPR · Unpaid', $breakdown['2026-07-15']['type_label']);
        $this->assertEquals(500.00, $breakdown['2026-07-15']['deducted_amount']);
        $this->assertEquals(0.5, $breakdown['2026-07-15']['deduction_factor']);
    }

    /**
     * Test UPA manual override propagation and balance consumption.
     */
    public function test_upa_propagation_and_balance_consumption(): void
    {
        // Give employee exactly 1.0 day of leave balance
        $this->employee->update(['leave_balance' => 1.00]);
        if ($this->employee->leaveBalance) {
            $this->employee->leaveBalance->update(['remaining_leave' => 1.00]);
        }

        // Apply UPA override
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee->id],
            'date_mode' => 'single',
            'date' => '2026-07-16',
            'status' => 'upa',
            'classification' => 'automatic',
            'override_reason' => 'Testing UPA sync override',
            'conflict_handling' => 'replace',
        ]);

        $response->assertRedirect();

        $att = Attendance::where('user_id', $this->employee->id)->where('date', '2026-07-16 00:00:00')->first();
        $this->assertNotNull($att);
        $this->assertEquals('upa', $att->status);
        $this->assertEquals('full_day', $att->classification);

        $attendanceService = app(\App\Services\AttendanceService::class);
        $state = $attendanceService->resolveStateForDate($this->employee, Carbon::parse('2026-07-16'), $att);
        $this->assertEquals('upa', $state['status']);

        // Payroll calculation must reflect UPA paid leave because leave balance is 1.0
        $payrollRecord = PayrollRecord::where('user_id', $this->employee->id)->where('payroll_cycle_id', $this->cycle->id)->first();
        $breakdown = $payrollRecord->calculation_metadata['daily_breakdown'];
        
        $this->assertEquals('upa', $breakdown['2026-07-16']['status']);
        $this->assertEquals('UPA · Paid Leave', $breakdown['2026-07-16']['type_label']);
        $this->assertEquals(0.00, $breakdown['2026-07-16']['deducted_amount']);
    }

    /**
     * Test explaining why 0 records matched instead of saying applied successfully to 0 record(s).
     */
    public function test_override_zero_records_explains_reason(): void
    {
        // 1. Create an override on July 10
        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-07-10 00:00:00',
            'status' => 'present',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_reason' => 'Previous override',
        ]);

        // 2. Post override targeting the same user on July 10, but with skip_overrides = true, resulting in 0 changes
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee->id],
            'date_mode' => 'single',
            'date' => '2026-07-10',
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'Testing 0 records override',
            'conflict_handling' => 'skip',
            'skip_overrides' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['override_reason']);
        $this->assertStringContainsString('No records were modified. Skipped 0 record(s) with approved leave and 1 record(s) with existing overrides.', session('errors')->first('override_reason'));
    }
}
