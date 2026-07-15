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
use App\Services\PayrollEligibilityService;
use App\Services\PayrollCycleResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpgradedPayrollWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        // Admin User
        $this->admin = User::factory()->create([
            'name' => 'Rhea Sarin',
            'email' => 'rhea@example.com',
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
        ]);

        // Standard Employee User
        $this->employee = User::factory()->create([
            'name' => 'Arjun Bisht',
            'email' => 'arjun@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-18', // June 18 BRS example
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        $this->employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-06-18',
            'payroll_enabled' => true,
        ]);
    }

    /** @test */
    public function admin_users_are_excluded_from_payroll_population()
    {
        // Admin has a payroll profile too
        $this->admin->payrollProfile()->create([
            'base_salary' => 120000.00,
            'salary_effective_date' => '2025-01-10',
            'payroll_enabled' => true,
        ]);

        $eligible = PayrollEligibilityService::getEligibleEmployees(2026, 6);

        // Expect standard employee to be included but admin to be excluded
        $this->assertTrue($eligible->contains('id', $this->employee->id));
        $this->assertFalse($eligible->contains('id', $this->admin->id));
    }

    /** @test */
    public function payroll_ineligible_users_are_excluded()
    {
        // Set standard employee payroll_enabled = false
        $this->employee->payrollProfile->update(['payroll_enabled' => false]);

        $eligible = PayrollEligibilityService::getEligibleEmployees(2026, 6);

        $this->assertFalse($eligible->contains('id', $this->employee->id));
    }

    /** @test */
    public function employee_joining_after_cycle_end_is_excluded()
    {
        // Employee joining date is July 1st, 2026.
        // Checking June 2026 cycle.
        $futureEmployee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-07-01',
        ]);
        $futureEmployee->payrollProfile()->create([
            'base_salary' => 60000.00,
            'salary_effective_date' => '2026-07-01',
            'payroll_enabled' => true,
        ]);

        $eligible = PayrollEligibilityService::getEligibleEmployees(2026, 6);

        $this->assertFalse($eligible->contains('id', $futureEmployee->id));
    }

    /** @test */
    public function employee_joining_mid_cycle_is_included_with_bounded_eligible_period()
    {
        // Arjun joined June 18, 2026.
        // His June 2026 cycle should bound start date to June 18.
        $resolver = new PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($this->employee, 2026, 6);

        $this->assertNotNull($cycleInfo);
        $this->assertEquals('2026-06-18', $cycleInfo['start_date']->format('Y-m-d'));
        $this->assertEquals('2026-06-19', $cycleInfo['end_date']->format('Y-m-d'));
    }

    /** @test */
    public function terminated_employee_handling_excludes_correctly_based_on_separation_date()
    {
        // 1. Employee separated before June 2026 cycle (separated on May 15)
        $this->employee->employeeProfile->update([
            'separation_date' => '2026-05-15',
        ]);

        $eligible = PayrollEligibilityService::getEligibleEmployees(2026, 6);
        $this->assertFalse($eligible->contains('id', $this->employee->id));

        // 2. Reset separation to mid-cycle (separated June 19)
        $this->employee->employeeProfile->update([
            'separation_date' => '2026-06-19',
        ]);

        $eligibleMid = PayrollEligibilityService::getEligibleEmployees(2026, 6);
        $this->assertTrue($eligibleMid->contains('id', $this->employee->id));

        // Bounded cycle should cap at separation date
        $resolver = new PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($this->employee, 2026, 6);
        $this->assertNotNull($cycleInfo);
        $this->assertEquals('2026-06-19', $cycleInfo['end_date']->format('Y-m-d'));
    }

    /** @test */
    public function date_effective_salary_history_resolution_is_respected()
    {
        // Base is 50,000 effective June 18.
        // Create an older history of 40,000 effective June 1.
        $profile = $this->employee->payrollProfile;
        $profile->recordSalaryRevision(40000.00, '2026-06-01', 'Initial setup', null, 'Manual');
        $profile->recordSalaryRevision(50000.00, '2026-06-18', 'Joining hike', null, 'Manual');

        // Resolve on June 10
        $salaryJune10 = PayrollService::resolveBaseSalaryForDate($this->employee, Carbon::parse('2026-06-10'));
        $this->assertEquals(40000.00, $salaryJune10);

        // Resolve on June 20
        $salaryJune20 = PayrollService::resolveBaseSalaryForDate($this->employee, Carbon::parse('2026-06-20'));
        $this->assertEquals(50000.00, $salaryJune20);
    }

    /** @test */
    public function approved_birthday_leave_does_not_become_unpaid_attendance_deduction()
    {
        // Create Birthday Leave request for June 18, 2026 (joining date/birthday example)
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'complimentary',
            'start_date' => '2026-06-18',
            'end_date' => '2026-06-18',
            'total_days' => 1.0,
            'is_half_day' => false,
            'status' => 'approved',
            'is_paid' => true,
            'reason' => 'Happy Birthday!',
        ]);

        // Create check-in on the other day of the cycle (June 19) to prevent absent deduction
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-19',
            'check_in_time' => '2026-06-19 09:00:00',
            'check_out_time' => '2026-06-19 17:30:00',
            'status' => 'present',
            'classification' => 'full_day',
        ]);

        $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);

        // Birthday leave should be fully paid (0.00 deductions)
        $this->assertEquals(0.00, $calc['attendance_deductions']);
        $this->assertEquals(0.00, $calc['leave_deductions']);
        $this->assertEquals(1.0, $calc['birthday_leave_days']);
    }
}
