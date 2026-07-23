<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\EmployeeProfile;
use App\Models\PayrollSetting;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeePayrollApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_approve_payroll_via_route()
    {
        PayrollSetting::seedDefaults();

        $admin = User::factory()->create(['role' => 'admin', 'joining_date' => '2025-01-01']);
        $employee = User::factory()->create(['role' => 'employee', 'joining_date' => '2026-01-01']);
        
        EmployeeProfile::create([
            'user_id' => $employee->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        $employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);

        $cycle = PayrollService::processCycle('June 2026', $admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $employee->id)->first();

        // 1. Check index page renders and contains the record details
        $response = $this->actingAs($employee)
            ->get(route('employee.payroll.index', ['period' => 'June 2026']));
        
        $response->assertStatus(200);
        $response->assertSee('Confirm & Approve', false); // Literal check
        $response->assertSee(route('employee.payroll.approve'));

        // 2. Submit the approval POST
        $response = $this->actingAs($employee)
            ->post(route('employee.payroll.approve'), [
                'record_id' => $record->id,
            ]);

        $response->assertRedirect();
        $record->refresh();
        $this->assertEquals('approved', $record->employee_review_status);
    }

    public function test_dry_run_does_not_crash_index_page()
    {
        PayrollSetting::seedDefaults();

        $employee = User::factory()->create(['role' => 'employee', 'joining_date' => '2026-01-01']);
        
        EmployeeProfile::create([
            'user_id' => $employee->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        $employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-01-01',
            'payroll_enabled' => true,
        ]);

        // Access index page when NO cycle exists for a period (causes dry run, record is null/unsaved)
        $response = $this->actingAs($employee)
            ->get(route('employee.payroll.index', ['period' => 'June 2026']));
        
        $response->assertStatus(200);
    }
}
