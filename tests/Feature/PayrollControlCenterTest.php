<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollCorrection;
use App\Models\PayrollException;
use App\Models\EmployeeProfile;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed defaults
        PayrollSetting::seedDefaults();

        // Create Admin
        $this->admin = User::factory()->create([
            'name' => 'Rhea Sarin',
            'email' => 'rhea@example.com',
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
        ]);

        // Create Employee
        $this->employee = User::factory()->create([
            'name' => 'Arjun Bisht',
            'email' => 'arjun@example.com',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-18', // Arjun Bisht BRS example!
        ]);

        // Create employee profile
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Frontend Engineer',
            'employee_category' => 'Permanent',
        ]);

        // Create payroll profile
        $this->employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-06-18',
            'payroll_enabled' => true,
            'import_source' => 'Manual',
        ]);
    }

    /** @test */
    public function only_admins_can_access_payroll_control_center()
    {
        // Guests redirect to login
        $this->get(route('admin.payroll.index'))->assertRedirect(route('login'));

        // Employees get access denied (forbidden)
        $this->actingAs($this->employee)->get(route('admin.payroll.index'))->assertStatus(403);

        // Admins get OK
        $this->actingAs($this->admin)->get(route('admin.payroll.index'))->assertOk();
    }

    /** @test */
    public function it_calculates_and_resolves_standard_vs_bridge_cycles_correctly()
    {
        // 1. June 2026 Cycle (month of joining)
        // Arjun joined June 18.
        // His June cycle should be initial_partial: June 18 to June 19 (2 days)
        $calcJune = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 6);
        $this->assertEquals('initial_partial', $calcJune['cycle_type']);
        $this->assertEquals('2026-06-18', $calcJune['start_date']);
        $this->assertEquals('2026-06-19', $calcJune['end_date']);

        // 2. July 2026 Cycle
        // Arjun is still under 120 days. July cycle should be standard_20_20: June 20 to July 20.
        $calcJuly = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 7);
        $this->assertEquals('standard_20_20', $calcJuly['cycle_type']);
        $this->assertEquals('2026-06-20', $calcJuly['start_date']);
        $this->assertEquals('2026-07-20', $calcJuly['end_date']);

        // 3. September 2026 Cycle
        // Arjun reaches 120 days on October 16.
        // During the next cycle (Sept 21 to Oct 20), he would cross 120 days.
        // Therefore, the September cycle is his bridge cycle: August 21 to September 30!
        $calcSept = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 9);
        $this->assertEquals('bridge', $calcSept['cycle_type']);
        $this->assertEquals('2026-08-21', $calcSept['start_date']);
        $this->assertEquals('2026-09-30', $calcSept['end_date']);

        // 4. October 2026 Cycle
        // Now he has transitioned to the standard calendar-month cycle: October 1 to October 31.
        $calcOct = PayrollService::calculateMonthlyPayroll($this->employee, 2026, 10);
        $this->assertEquals('calendar_month', $calcOct['cycle_type']);
        $this->assertEquals('2026-10-01', $calcOct['start_date']);
        $this->assertEquals('2026-10-31', $calcOct['end_date']);
    }

    /** @test */
    public function it_runs_cycle_processing_and_detects_exceptions()
    {
        $this->actingAs($this->admin);

        // Process June 2026 cycle
        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        
        $this->assertNotNull($cycle);
        $this->assertEquals('June 2026', $cycle->period);

        // Record should be created
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
            ->where('user_id', $this->employee->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(50000.00, $record->base_salary);

        // Since Arjun has no check-ins recorded, he should have absent days
        // which flags a "Missing Attendance" warning exception
        $exception = PayrollException::where('payroll_cycle_id', $cycle->id)
            ->where('user_id', $this->employee->id)
            ->where('type', 'Missing Attendance')
            ->first();

        $this->assertNotNull($exception);
        $this->assertEquals('Warning', $exception->severity);
    }

    /** @test */
    public function it_submits_and_approves_corrections_properly()
    {
        $this->actingAs($this->admin);

        $cycle = PayrollService::processCycle('June 2026', $this->admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->firstOrFail();

        $oldNet = $record->net_salary;

        // Submit a manual correction of ₹2,500
        $response = $this->postJson(route('admin.payroll.corrections.store'), [
            'record_id' => $record->id,
            'new_net_salary' => $oldNet + 2500,
            'reason' => 'Approved bonus for manager signoff',
        ]);

        $response->assertJsonPath('success', true);

        // Fetch correction and approve it
        $correction = PayrollCorrection::where('payroll_record_id', $record->id)->firstOrFail();
        $this->assertEquals('pending', $correction->approval_status);

        $approveResponse = $this->postJson(route('admin.payroll.corrections.approve', ['id' => $record->id]));
        $approveResponse->assertJsonPath('success', true);

        // Verify record is updated
        $record->refresh();
        $this->assertEquals($oldNet + 2500, $record->net_salary);
        $this->assertEquals('approved', $record->status);
    }

    /** @test */
    public function it_locks_and_unlocks_cycles_successfully()
    {
        $this->actingAs($this->admin);

        // Create check-in/out records for Arjun to make his net salary positive and prevent negative salary exceptions
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-18',
            'check_in_time' => '09:30:00',
            'check_out_time' => '17:30:00',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-19',
            'check_in_time' => '09:30:00',
            'check_out_time' => '17:30:00',
            'status' => 'present',
        ]);

        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        // Check lock
        $response = $this->post(route('admin.payroll.lock'), [
            'period' => 'June 2026',
        ]);

        $cycle->refresh();
        $this->assertEquals('locked', $cycle->status);

        // Check unlock
        $responseUnlock = $this->post(route('admin.payroll.unlock'), [
            'period' => 'June 2026',
            'reason' => 'Need adjustment on basic structure',
        ]);

        $cycle->refresh();
        $this->assertEquals('under_review', $cycle->status);
    }

    /** @test */
    public function it_exports_disbursement_ledger_csv()
    {
        $this->actingAs($this->admin);
        
        $cycle = PayrollService::processCycle('June 2026', $this->admin);

        $response = $this->get(route('admin.payroll.ledger.export', ['period' => 'June 2026']));
        
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=Salary_Ledger_June 2026.csv');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Employee ID', $content);
        $this->assertStringContainsString('Name,Department,Designation', $content);
        $this->assertStringContainsString('Arjun Bisht', $content);
    }
}
