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

    private function setupCycleAndRecord(string $period, string $cycleStatus, bool $recordLocked): array
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

        $cycle = PayrollService::processCycle($period, $admin);
        $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)->where('user_id', $employee->id)->firstOrFail();

        // Adjust cycle status
        $cycle->update(['status' => $cycleStatus]);

        // Adjust record lock status
        $record->update(['locked' => $recordLocked]);

        return [$admin, $employee, $cycle, $record];
    }

    public function test_locked_cycle_locked_record_denied()
    {
        list($admin, $employee, $cycle, $record) = $this->setupCycleAndRecord('June 2026', 'locked', true);

        // Approve workflow -> Denied
        $approved = PayrollService::approveEmployeeRecord($record, $employee);
        $this->assertFalse($approved);

        // Dispute workflow -> Denied
        try {
            PayrollService::disputeEmployeeRecord($record, $employee, [
                'category' => 'Attendance',
                'description' => 'Dispute description must be at least ten characters long.',
                'expected_correction' => 'None',
                'affected_date' => null,
            ]);
            $this->fail("Expected exception not thrown for dispute on locked record under locked cycle.");
        } catch (\Exception $e) {
            $this->assertEquals("Cannot raise a dispute on a locked payroll record.", $e->getMessage());
        }
    }

    public function test_locked_cycle_reopened_record_allowed()
    {
        list($admin, $employee, $cycle, $record) = $this->setupCycleAndRecord('June 2026', 'locked', false);

        // Dispute workflow -> Allowed
        $dispute = PayrollService::disputeEmployeeRecord($record, $employee, [
            'category' => 'Attendance',
            'description' => 'Dispute description must be at least ten characters long.',
            'expected_correction' => 'None',
            'affected_date' => null,
        ]);
        $this->assertNotNull($dispute);
        $this->assertEquals('disputed', $record->fresh()->employee_review_status);

        // Reset status for approval test
        $record->update(['employee_review_status' => 'pending']);

        // Approve workflow -> Allowed
        $approved = PayrollService::approveEmployeeRecord($record, $employee);
        $this->assertTrue($approved);
        $this->assertEquals('approved', $record->fresh()->employee_review_status);
    }

    public function test_unlocked_cycle_unlocked_record_allowed()
    {
        list($admin, $employee, $cycle, $record) = $this->setupCycleAndRecord('June 2026', 'generated', false);

        // Dispute workflow -> Allowed
        $dispute = PayrollService::disputeEmployeeRecord($record, $employee, [
            'category' => 'Attendance',
            'description' => 'Dispute description must be at least ten characters long.',
            'expected_correction' => 'None',
            'affected_date' => null,
        ]);
        $this->assertNotNull($dispute);
        $this->assertEquals('disputed', $record->fresh()->employee_review_status);

        // Reset status for approval test
        $record->update(['employee_review_status' => 'pending']);

        // Approve workflow -> Allowed
        $approved = PayrollService::approveEmployeeRecord($record, $employee);
        $this->assertTrue($approved);
        $this->assertEquals('approved', $record->fresh()->employee_review_status);
    }

    public function test_unlocked_cycle_locked_record_denied()
    {
        list($admin, $employee, $cycle, $record) = $this->setupCycleAndRecord('June 2026', 'generated', true);

        // Approve workflow -> Denied
        $approved = PayrollService::approveEmployeeRecord($record, $employee);
        $this->assertFalse($approved);

        // Dispute workflow -> Denied
        try {
            PayrollService::disputeEmployeeRecord($record, $employee, [
                'category' => 'Attendance',
                'description' => 'Dispute description must be at least ten characters long.',
                'expected_correction' => 'None',
                'affected_date' => null,
            ]);
            $this->fail("Expected exception not thrown for dispute on locked record under unlocked cycle.");
        } catch (\Exception $e) {
            $this->assertEquals("Cannot raise a dispute on a locked payroll record.", $e->getMessage());
        }
    }
}
