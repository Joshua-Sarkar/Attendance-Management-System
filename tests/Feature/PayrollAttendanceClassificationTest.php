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
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class PayrollAttendanceClassificationTest extends TestCase
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
            'email' => 'admin@venturerequest.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-01',
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP10001',
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Software Engineer',
            'employee_category' => 'Permanent',
        ]);

        $payrollProfile = PayrollProfile::create([
            'user_id' => $this->employee->id,
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        $payrollProfile->salaryHistories()->create([
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-06-01',
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
     * Helper to create manual attendance override status on a date.
     */
    protected function createOverride(string $dateStr, string $status)
    {
        return Attendance::create([
            'user_id' => $this->employee->id,
            'date' => $dateStr,
            'status' => $status,
            'is_overridden' => true,
            'override_reason' => 'Test manual override to ' . $status,
        ]);
    }

    /**
     * Test all 11 statuses and their canonical salary effects.
     */
    public function test_all_eleven_statuses_and_salary_effects(): void
    {
        // July has 31 days. Daily rate is 30,000 / 31 = 967.74
        $dailyRate = 30000.00 / 31.0;

        $testStatuses = [
            'present' => 0.0,
            'off'     => 0.0,
            'planned' => 0.0,
            'bday'    => 0.0,
            'hdp'     => 0.0,
            'half'    => 0.5,
            'hd_upr'  => 0.5,
            'absent'  => 1.0,
            'upr'     => 1.0,
        ];

        foreach ($testStatuses as $status => $expectedDeductionFactor) {
            // Reset attendance
            Attendance::where('user_id', $this->employee->id)->delete();

            // Override a single day
            $this->createOverride('2026-07-15', $status);

            // Calculate payroll
            $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 7);

            $expectedDeduction = round($expectedDeductionFactor * $dailyRate, 2);
            $actualDeduction = $calc['daily_breakdown']['2026-07-15']['deducted_amount'];
            $this->assertEquals($expectedDeduction, $actualDeduction, "Failed status deduction mapping for: {$status}");
        }
    }

    /**
     * Test unplanned approved leave (UPA) chronological balance consumption.
     */
    public function test_unplanned_approved_leave_balance_consumption(): void
    {
        // Give employee exactly 1.5 days of leave balance
        $this->employee->update(['leave_balance' => 1.50]);
        if ($this->employee->leaveBalance) {
            $this->employee->leaveBalance->update(['remaining_leave' => 1.50]);
        }

        // Create approved unplanned leaves:
        // July 10: 1.0 day UPA (consumes 1.0, 0.5 balance remains)
        // July 11: 1.0 day UPA (consumes 0.5, 0.5 unpaid remains)
        $this->createOverride('2026-07-10', 'upa');
        $this->createOverride('2026-07-11', 'upa');

        $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 7);

        $dailyRate = 30000.00 / 31.0;
        $expectedDeduction = round(0.5 * $dailyRate, 2);

        $this->assertEquals(0.5, $calc['unpaid_leave_days']);
        $this->assertEquals(1.5, $calc['paid_unplanned_leaves']);

        // Verify Daily Breakdown labels mirror the status and payment state
        $breakdown = $calc['daily_breakdown'];
        
        $this->assertEquals('upa', $breakdown['2026-07-10']['status']);
        $this->assertEquals('UPA · Paid Leave', $breakdown['2026-07-10']['type_label']);
        $this->assertEquals(0.00, $breakdown['2026-07-10']['deducted_amount']);

        $this->assertEquals('upa', $breakdown['2026-07-11']['status']);
        $this->assertEquals('UPA · Unpaid Portion', $breakdown['2026-07-11']['type_label']);
        $this->assertEquals($expectedDeduction, $breakdown['2026-07-11']['deducted_amount']);
    }

    /**
     * Test lock and unlock leave balance adjustment behavior.
     */
    public function test_lock_and_unlock_balance_consumption_flow(): void
    {
        // Give employee exactly 2.0 days of leave balance
        $this->employee->update(['leave_balance' => 2.00]);
        if ($this->employee->leaveBalance) {
            $this->employee->leaveBalance->update(['remaining_leave' => 2.00]);
        }

        // Apply UPA override on July 10 (consumes 1.0 day balance)
        $this->createOverride('2026-07-10', 'upa');

        // Verify calculations
        $record = PayrollRecord::where('payroll_cycle_id', $this->cycle->id)->where('user_id', $this->employee->id)->first();
        
        PayrollService::recalculateRecord($record, $this->admin);
        $record->refresh();
        
        $this->assertEquals(0.0, (float)$record->unpaid_leave_days);
        $this->assertEquals(1.0, (float)($record->calculation_metadata['paid_unplanned_leaves'] ?? 0.0));

        // Employee balance in DB is still 2.0 because cycle is unlocked
        $this->employee->refresh();
        $this->assertEquals(2.0, (float)$this->employee->leave_balance);

        // Lock the cycle
        PayrollService::lockCycle($this->cycle, $this->admin);

        // After lock: employee balance must be 2.0 - 1.0 = 1.0
        $this->employee->refresh();
        $this->assertEquals(1.0, (float)$this->employee->leave_balance);

        // Metadata must record how much was deducted
        $record->refresh();
        $this->assertEquals(1.0, (float)($record->calculation_metadata['paid_unplanned_leaves_deducted'] ?? 0.0));

        // Unlock the cycle
        PayrollService::unlockCycle($this->cycle, 're-evaluating attendance override', $this->admin);

        // After unlock: employee balance must be refunded back to 2.0
        $this->employee->refresh();
        $this->assertEquals(2.0, (float)$this->employee->leave_balance);

        // Metadata must reset paid_unplanned_leaves_deducted to 0.0
        $record->refresh();
        $this->assertEquals(0.0, (float)($record->calculation_metadata['paid_unplanned_leaves_deducted'] ?? 0.0));
    }
}
