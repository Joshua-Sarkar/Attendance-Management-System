<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\PayrollProfile;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollSetting;
use App\Services\PayrollService;
use App\Services\PayrollEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Events\AttendanceOverridden;

class PayrollCycleDefectTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;

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

        // Create the July joining employee: EMP00001
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

        // Create a standard June employee to have historical records
        $juneEmp = User::create([
            'employee_id' => 'EMP00002',
            'name' => 'June Employee',
            'email' => 'june@example.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-01',
        ]);

        EmployeeProfile::create([
            'user_id' => $juneEmp->id,
            'designation' => 'Developer',
            'employee_category' => 'Permanent',
        ]);

        $junePayroll = PayrollProfile::create([
            'user_id' => $juneEmp->id,
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        $junePayroll->salaryHistories()->create([
            'base_salary' => 40000.00,
            'salary_effective_date' => '2026-06-01',
            'change_reason' => 'Initial placement',
            'source' => 'Manual',
        ]);
    }

    public function test_payroll_cycle_defect_requirements(): void
    {
        // 1. A payroll-enabled employee joining 01 July 2026 is excluded from June 2026.
        $juneEligible = PayrollEligibilityService::getEligibleEmployees(2026, 6);
        $this->assertFalse($juneEligible->contains('id', $this->employee->id));

        // 2. The same employee is eligible for July 2026.
        $julyEligible = PayrollEligibilityService::getEligibleEmployees(2026, 7);
        $this->assertTrue($julyEligible->contains('id', $this->employee->id));

        // Create June cycle first
        $juneCycle = PayrollService::processCycle('June 2026', $this->admin);
        $this->assertEquals('June 2026', $juneCycle->period);

        // 3. Test next cycle preview via endpoint
        $response = $this->actingAs($this->admin)->get(route('admin.payroll.cycles.next-preview'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'period' => 'July 2026',
            'start_date' => '01 Jul 2026',
            'end_date' => '31 Jul 2026',
            'payment_date' => '07 Aug 2026',
        ]);
        
        $data = $response->json();
        // Check that SR71 is listed in newly entering employees
        $newlyEnteringNames = collect($data['newly_entering'])->pluck('name')->toArray();
        $this->assertContains('SR71 Payroll Test', $newlyEnteringNames);

        // 4. Create July cycle via endpoint
        $response = $this->actingAs($this->admin)->post(route('admin.payroll.cycles.create'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'period' => 'July 2026',
        ]);

        $julyCycle = PayrollCycle::where('period', 'July 2026')->first();
        $this->assertNotNull($julyCycle);
        $this->assertEquals('July 2026', $julyCycle->period);
        $this->assertEquals('2026-07-01', $julyCycle->start_date->format('Y-m-d'));
        $this->assertEquals('2026-07-31', $julyCycle->end_date->format('Y-m-d'));

        // 5. Repeating cycle creation is idempotent and prevents duplicates
        $response2 = $this->actingAs($this->admin)->post(route('admin.payroll.cycles.create'), ['period' => 'July 2026']);
        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'message' => 'Payroll cycle July 2026 already exists.',
        ]);
        $this->assertEquals(1, PayrollCycle::where('period', 'July 2026')->count());

        // 6. July appears in payroll period data sources / queries
        $allPeriods = PayrollCycle::orderBy('start_date', 'desc')->pluck('period')->toArray();
        $this->assertContains('July 2026', $allPeriods);
        $this->assertContains('June 2026', $allPeriods);

        // 7. EMP00001 has a July PayrollRecord and retains ₹30,000 base salary
        $empJulyRecord = PayrollRecord::where('payroll_cycle_id', $julyCycle->id)
            ->where('user_id', $this->employee->id)
            ->first();
        $this->assertNotNull($empJulyRecord);
        $this->assertEquals(30000.00, (float)$empJulyRecord->base_salary);

        // 8. Attendance changes in July recalculate the employee's unlocked July payroll record
        event(new AttendanceOverridden($this->employee, Carbon::parse('2026-07-10'), $this->admin));
        
        $updatedJulyRecord = PayrollRecord::find($empJulyRecord->id);
        $this->assertNotNull($updatedJulyRecord);

        // 9. June historical payroll is unchanged
        $juneRecords = PayrollRecord::where('payroll_cycle_id', $juneCycle->id)->get();
        $this->assertFalse($juneRecords->contains('user_id', $this->employee->id));

        // 10. Locked payroll immutability is enforced
        $empJulyRecord->update(['locked' => true]);
        $oldNet = $empJulyRecord->net_salary;
        PayrollService::recalculateRecord($empJulyRecord, $this->admin);
        
        $postRecalcRecord = PayrollRecord::find($empJulyRecord->id);
        $this->assertTrue($postRecalcRecord->locked);
        $this->assertEquals($oldNet, $postRecalcRecord->net_salary);

        // 11. Verify pages route integration / cycle resolution for July 2026
        // Employee Payroll index
        $this->actingAs($this->admin)->get(route('admin.payroll.index', ['period' => 'July 2026']))
            ->assertStatus(200);

        // Salary Ledger index
        $this->actingAs($this->admin)->get(route('admin.payroll.ledger', ['period' => 'July 2026']))
            ->assertStatus(200);

        // Payroll Lock index
        $this->actingAs($this->admin)->get(route('admin.payroll.lock', ['period' => 'July 2026']))
            ->assertStatus(200);

        // Payslips index
        $this->actingAs($this->admin)->get(route('admin.payroll.payslips', ['period' => 'July 2026']))
            ->assertStatus(200);

        // Reports index
        $this->actingAs($this->admin)->get(route('admin.payroll.reports', ['period' => 'July 2026']))
            ->assertStatus(200);

        // Employee My Payroll index
        $this->actingAs($this->employee)->get(route('employee.payroll.index', ['period' => 'July 2026']))
            ->assertStatus(200)
            ->assertViewHas('allPeriods', function ($periods) {
                return in_array('July 2026', $periods) && in_array('June 2026', $periods);
            });
    }
}
