<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveCredit;
use App\Models\LeaveRequestLog;
use App\Models\PayrollProfile;
use App\Models\SalaryHistory;
use App\Models\EmployeeExternalIdentifier;
use App\Models\Attendance;
use App\Models\EmployeeProfile;
use App\Models\ProfileCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotfixLeaveAndEmployeeDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_submit_unplanned_leave_for_future_dates()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $futureDate = Carbon::today()->addDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($employee)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $futureDate,
            'end_date' => $futureDate,
            'reason' => 'Emergency in the future',
        ]);
        
        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'unplanned',
        ]);
    }

    public function test_employee_can_submit_unplanned_leave_for_today()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $todayStr = Carbon::today()->format('Y-m-d');
        
        $response = $this->actingAs($employee)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $todayStr,
            'end_date' => $todayStr,
            'reason' => 'Emergency today',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'unplanned',
            'status' => 'pending',
            'start_date' => Carbon::parse($todayStr)->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_employee_can_submit_unplanned_leave_for_past_dates()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $pastStr = Carbon::today()->subDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($employee)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $pastStr,
            'end_date' => $pastStr,
            'reason' => 'Emergency in the past',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'unplanned',
            'status' => 'pending',
            'start_date' => Carbon::parse($pastStr)->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_employee_cannot_bypass_backend_validation_direct_service_call()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $futureDate = Carbon::today()->addDays(2)->format('Y-m-d');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unplanned Leave may only be requested for Past or Today dates.');
        
        \App\Services\LeaveBalanceService::applyRequest($employee, [
            'leave_type' => 'unplanned',
            'start_date' => $futureDate,
            'end_date' => $futureDate,
            'reason' => 'Direct service call bypass test',
        ]);
    }

    public function test_admin_leave_requests_are_auto_approved()
    {
        $admin = User::factory()->create(['role' => 'admin', 'leave_balance' => 10.00]);
        
        // 1. Planned
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(4)->format('Y-m-d'); // 3 days
        
        $response = $this->actingAs($admin)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Admin break',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $admin->id,
            'leave_type' => 'planned',
            'status' => 'approved',
            'approver_id' => $admin->id,
        ]);
        
        $admin->refresh();
        $this->assertEquals(7.00, $admin->leave_balance);
        
        // 2. Unplanned
        $pastDate = Carbon::today()->subDays(2)->format('Y-m-d');
        $responseUnplanned = $this->actingAs($admin)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'reason' => 'Admin unplanned emergency',
        ]);
        
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $admin->id,
            'leave_type' => 'unplanned',
            'status' => 'approved',
            'approver_id' => $admin->id,
        ]);
    }

    public function test_employee_planned_leave_remains_pending()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $startDate,
            'reason' => 'Employee casual break',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'status' => 'pending',
            'approver_id' => null,
        ]);
        
        $employee->refresh();
        $this->assertEquals(10.00, $employee->leave_balance);
    }

    public function test_employee_unplanned_leave_remains_pending()
    {
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $pastDate = Carbon::today()->subDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($employee)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'reason' => 'Employee unplanned break',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => 'unplanned',
            'status' => 'pending',
            'approver_id' => null,
        ]);
    }

    public function test_manager_leave_remains_pending()
    {
        $manager = User::factory()->create(['role' => 'manager', 'leave_balance' => 10.00]);
        
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($manager)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $startDate,
            'reason' => 'Manager break',
        ]);
        
        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $manager->id,
            'leave_type' => 'planned',
            'status' => 'pending',
            'approver_id' => null,
        ]);
        
        $manager->refresh();
        $this->assertEquals(10.00, $manager->leave_balance);
    }

    public function test_admin_can_delete_employee_safely()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'leave_balance' => 10.00]);
        
        $employee->employeeProfile()->create([
            'designation' => 'Software Engineer',
            'location' => 'San Francisco',
        ]);
        
        $payroll = $employee->payrollProfile()->create([
            'base_salary' => 50000.00,
            'payroll_enabled' => true,
        ]);
        
        $salaryHistory = SalaryHistory::create([
            'payroll_profile_id' => $payroll->id,
            'base_salary' => 50000.00,
            'salary_effective_date' => Carbon::today(),
            'source' => 'Manual',
        ]);
        
        $employee->leaveBalance()->create([
            'remaining_leave' => 10.00,
            'utilized_leave' => 0.00,
        ]);
        
        $identifier = EmployeeExternalIdentifier::create([
            'user_id' => $employee->id,
            'source' => 'Slack',
            'external_identifier' => 'U12345',
        ]);
        
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'date' => Carbon::today(),
            'status' => 'present',
        ]);
        
        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(2),
            'total_days' => 1.0,
            'status' => 'pending',
            'reason' => 'vacation',
        ]);
        
        $log = LeaveRequestLog::create([
            'leave_request_id' => $leaveRequest->id,
            'user_id' => $employee->id,
            'action' => 'applied',
            'to_status' => 'pending',
        ]);
        
        $ledger = LeaveLedgerEntry::create([
            'user_id' => $employee->id,
            'leave_request_id' => $leaveRequest->id,
            'amount' => -1.0,
            'type' => 'deduction',
            'description' => 'Applied',
        ]);
        
        $correction = ProfileCorrectionRequest::create([
            'user_id' => $employee->id,
            'field' => 'name',
            'message' => 'Please correct my name',
            'status' => 'pending',
        ]);
        
        // Assert all exist in DB before deletion
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('employee_profiles', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('payroll_profiles', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('salary_histories', ['id' => $salaryHistory->id]);
        $this->assertDatabaseHas('leave_balances', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('employee_external_identifiers', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('attendances', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('leave_requests', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('leave_request_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('leave_ledger_entries', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('profile_correction_requests', ['user_id' => $employee->id]);
        
        // Trigger deletion
        $response = $this->actingAs($admin)->delete(route('employees.destroy', $employee));
        
        $response->assertRedirect(route('employees.index'));
        
        // Assert all are deleted
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('employee_profiles', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('payroll_profiles', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('salary_histories', ['id' => $salaryHistory->id]);
        $this->assertDatabaseMissing('leave_balances', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('employee_external_identifiers', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('leave_requests', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('leave_request_logs', ['id' => $log->id]);
        $this->assertDatabaseMissing('leave_ledger_entries', ['user_id' => $employee->id]);
        $this->assertDatabaseMissing('profile_correction_requests', ['user_id' => $employee->id]);
    }

    public function test_non_admin_cannot_delete_employee()
    {
        $employeeToDelete = User::factory()->create(['role' => 'employee']);
        
        $anotherEmployee = User::factory()->create(['role' => 'employee']);
        $manager = User::factory()->create(['role' => 'manager']);
        
        // 1. Employee tries to delete
        $response1 = $this->actingAs($anotherEmployee)->delete(route('employees.destroy', $employeeToDelete));
        $response1->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $employeeToDelete->id]);
        
        // 2. Manager tries to delete
        $response2 = $this->actingAs($manager)->delete(route('employees.destroy', $employeeToDelete));
        $response2->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $employeeToDelete->id]);
    }
}
