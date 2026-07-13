<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\PayrollProfile;
use App\Models\SalaryHistory;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollException;
use App\Models\LeaveRequest;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollDataProvenanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        // Admin user
        $this->admin = User::factory()->create([
            'name' => 'Rhea Sarin',
            'email' => 'rhea@example.com',
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
        ]);

        // Employee user
        $this->employee = User::factory()->create([
            'name' => 'Nimesh Singh',
            'email' => 'nimesh@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-01-05',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Graphic Designer',
            'employee_category' => 'Permanent',
        ]);
    }

    /** @test */
    public function test_a_exact_base_salary()
    {
        // Employee Payroll Profile Base Salary = 40,000
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-01-05',
            'payroll_enabled' => true,
        ]);

        $profile->recordSalaryRevision(40000.00, '2026-01-05', 'Initial', null, 'Manual');

        // Render index page for June 2026 cycle
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.payroll.index', ['period' => 'June 2026']));
        $response->assertOk();

        // Get record
        $record = PayrollRecord::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(40000.00, $record->base_salary);
    }

    /** @test */
    public function test_b_no_salary_configured()
    {
        // Employee has no valid Base Salary
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => null,
            'salary_effective_date' => null,
            'payroll_enabled' => true,
        ]);

        $this->actingAs($this->admin);
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        // Expect Exception "Missing Salary Structure"
        $exception = PayrollException::where('payroll_cycle_id', $cycle->id)
            ->where('user_id', $this->employee->id)
            ->where('type', 'Missing Salary Structure')
            ->first();

        $this->assertNotNull($exception);
        $this->assertEquals('Critical', $exception->severity);
    }

    /** @test */
    public function test_c_no_automatic_15_percent_uplift()
    {
        // Base Salary = 40,000, no earning components/allowances configured
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-01-05',
            'payroll_enabled' => true,
        ]);

        $profile->recordSalaryRevision(40000.00, '2026-01-05', 'Initial', null, 'Manual');

        // Create check-ins to make sure present all days (so no deductions)
        $resolver = new \App\Services\PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($this->employee, 2026, 6);
        $startDate = $cycleInfo['start_date'];
        $endDate = $cycleInfo['end_date'];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            \App\Models\Attendance::create([
                'user_id' => $this->employee->id,
                'date' => $current->format('Y-m-d'),
                'check_in_time' => $current->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'check_out_time' => $current->copy()->setTime(17, 0)->format('Y-m-d H:i:s'),
                'status' => 'present',
            ]);
            $current->addDay();
        }

        $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);

        // Expected gross before attendance impact: 40,000
        $this->assertEquals(40000.00, $calc['gross_salary']);
        $this->assertEquals(0.00, $calc['allowances']);
    }

    /** @test */
    public function test_d_real_configured_earning_component()
    {
        // Base Salary = 40,000, Configured allowance = 5,000
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-01-05',
            'payroll_enabled' => true,
        ]);

        $profile->recordSalaryRevision(40000.00, '2026-01-05', 'Initial', null, 'Manual');

        // Configure allowance of 5000 in settings
        PayrollSetting::setValue('allowances', [
            $this->employee->id => 5000.00
        ]);

        // Present all days
        $resolver = new \App\Services\PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($this->employee, 2026, 6);
        $startDate = $cycleInfo['start_date'];
        $endDate = $cycleInfo['end_date'];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            \App\Models\Attendance::create([
                'user_id' => $this->employee->id,
                'date' => $current->format('Y-m-d'),
                'check_in_time' => $current->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'check_out_time' => $current->copy()->setTime(17, 0)->format('Y-m-d H:i:s'),
                'status' => 'present',
            ]);
            $current->addDay();
        }

        $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);

        // Expected gross salary: 40000 base + 5000 allowance = 45000.00
        $this->assertEquals(40000.00, $calc['base_salary']);
        $this->assertEquals(5000.00, $calc['allowances']);
        $this->assertEquals(45000.00, $calc['gross_salary']);
    }

    /** @test */
    public function test_e_historical_salary_resolution()
    {
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-07-01',
            'payroll_enabled' => true,
        ]);

        // 30,000 effective Jan 1
        $profile->recordSalaryRevision(30000.00, '2026-01-01', 'Initial', null, 'Manual');
        // 40,000 effective July 1
        $profile->recordSalaryRevision(40000.00, '2026-07-01', 'Promotion', null, 'Manual');

        // June 2026 Payroll should use 30,000
        $calcJune = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);
        $this->assertEquals(30000.00, $calcJune['base_salary']);

        // July 2026 Payroll should use 40,000
        $calcJuly = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 7);
        $this->assertEquals(40000.00, $calcJuly['base_salary']);
    }

    /** @test */
    public function test_f_calendar_day_daily_rate()
    {
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);
        $profile->recordSalaryRevision(30000.00, '2026-01-01', 'Initial', null, 'Manual');

        // June (30 days) daily rate = 30000 / 30 = 1000.00
        // If absent on June 1, deduction should be exactly 1000.00
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-01',
            'status' => 'absent',
        ]);

        // Other days present
        $current = Carbon::parse('2026-06-02');
        while ($current->lte(Carbon::parse('2026-06-30'))) {
            \App\Models\Attendance::create([
                'user_id' => $this->employee->id,
                'date' => $current->format('Y-m-d'),
                'check_in_time' => $current->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'check_out_time' => $current->copy()->setTime(17, 0)->format('Y-m-d H:i:s'),
                'status' => 'present',
            ]);
            $current->addDay();
        }

        $calcJune = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);
        $this->assertEquals(1000.00, $calcJune['attendance_deductions']);

        // July salary is updated to 31,000
        $profile->recordSalaryRevision(31000.00, '2026-07-01', 'Update', null, 'Manual');

        // Clean up attendances and create for July
        \App\Models\Attendance::query()->delete();

        // July (31 days) daily rate = 31000 / 31 = 1000.00
        // If absent on July 1, deduction should be exactly 1000.00
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-07-01',
            'status' => 'absent',
        ]);

        $current = Carbon::parse('2026-07-02');
        while ($current->lte(Carbon::parse('2026-07-31'))) {
            \App\Models\Attendance::create([
                'user_id' => $this->employee->id,
                'date' => $current->format('Y-m-d'),
                'check_in_time' => $current->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'check_out_time' => $current->copy()->setTime(17, 0)->format('Y-m-d H:i:s'),
                'status' => 'present',
            ]);
            $current->addDay();
        }

        $calcJuly = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 7);
        $this->assertEquals(1000.00, $calcJuly['attendance_deductions']);
    }

    /** @test */
    public function test_g_birthday_leave()
    {
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);
        $profile->recordSalaryRevision(30000.00, '2026-01-01', 'Initial', null, 'Manual');

        // Create approved Birthday Leave request
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'complimentary',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'total_days' => 1.0,
            'is_half_day' => false,
            'status' => 'approved',
            'is_paid' => true,
            'reason' => 'Birthday Leave',
        ]);

        // Present other days
        $current = Carbon::parse('2026-06-01');
        while ($current->lte(Carbon::parse('2026-06-30'))) {
            if ($current->format('Y-m-d') !== '2026-06-15') {
                \App\Models\Attendance::create([
                    'user_id' => $this->employee->id,
                    'date' => $current->format('Y-m-d'),
                    'check_in_time' => $current->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                    'check_out_time' => $current->copy()->setTime(17, 0)->format('Y-m-d H:i:s'),
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $calc = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);

        // Expected: no standard leave deduction, no unpaid leave deduction, no salary deduction
        $this->assertEquals(0.00, $calc['attendance_deductions']);
        $this->assertEquals(0.00, $calc['leave_deductions']);
        $this->assertEquals(1.0, $calc['birthday_leave_days']);
    }

    /** @test */
    public function test_h_recalculation()
    {
        $profile = $this->employee->payrollProfile()->create([
            'base_salary' => 30000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);
        $profile->recordSalaryRevision(30000.00, '2026-01-01', 'Initial', null, 'Manual');

        // Process June cycle
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
            ->where('user_id', $this->employee->id)
            ->first();

        $this->assertEquals(30000.00, $record->base_salary);

        // Admin updates salary manually to 40,000
        $profile->recordSalaryRevision(40000.00, '2026-01-01', 'Manual Update', null, 'Manual');

        // Recalculate
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        $record->refresh();
        $this->assertEquals(40000.00, $record->base_salary);
    }
}
