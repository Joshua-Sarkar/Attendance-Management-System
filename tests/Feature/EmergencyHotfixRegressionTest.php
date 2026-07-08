<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveLedgerEntry;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\AttendanceStateRegistry;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyHotfixRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = app(AttendanceService::class);
    }

    /**
     * Regression Test for Bug 1 (Grace Period Boundaries)
     */
    public function test_grace_boundary_inclusive(): void
    {
        // 1. Standard Shift (09:30 AM Shift Start, 5 min grace -> 09:35 AM boundary)
        $standardDept = Department::create([
            'name' => 'Engineering',
            'shift_start' => '09:30:00',
            'shift_end' => '17:30:00',
            'grace_minutes' => 5,
        ]);
        $emp1 = User::factory()->create([
            'role' => 'employee',
            'department_id' => $standardDept->id,
        ]);

        // Check in at 09:35:59 -> Should resolve to Present (On Time)
        Carbon::setTestNow(Carbon::today()->setTime(9, 35, 59));
        $att1 = $this->attendanceService->checkIn($emp1);
        $state1 = $this->attendanceService->resolveStateForDate($emp1, Carbon::today(), $att1);
        $this->assertEquals('present', $state1['status']);

        // Check in at 09:36:00 -> Should resolve to Late
        $emp2 = User::factory()->create([
            'role' => 'employee',
            'department_id' => $standardDept->id,
        ]);
        Carbon::setTestNow(Carbon::today()->setTime(9, 36, 0));
        $att2 = $this->attendanceService->checkIn($emp2);
        $state2 = $this->attendanceService->resolveStateForDate($emp2, Carbon::today(), $att2);
        $this->assertEquals('late', $state2['status']);

        // 2. Healthcare Shift (10:00 AM Shift Start, 5 min grace -> 10:05 AM boundary)
        $healthcareDept = Department::create([
            'name' => 'Healthcare',
            'shift_start' => '10:00:00',
            'shift_end' => '18:00:00',
            'grace_minutes' => 5,
        ]);
        $emp3 = User::factory()->create([
            'role' => 'employee',
            'department_id' => $healthcareDept->id,
        ]);

        // Check in at 10:05:59 -> Should resolve to Present (On Time)
        Carbon::setTestNow(Carbon::today()->setTime(10, 5, 59));
        $att3 = $this->attendanceService->checkIn($emp3);
        $state3 = $this->attendanceService->resolveStateForDate($emp3, Carbon::today(), $att3);
        $this->assertEquals('present', $state3['status']);

        // Check in at 10:06:00 -> Should resolve to Late
        $emp4 = User::factory()->create([
            'role' => 'employee',
            'department_id' => $healthcareDept->id,
        ]);
        Carbon::setTestNow(Carbon::today()->setTime(10, 6, 0));
        $att4 = $this->attendanceService->checkIn($emp4);
        $state4 = $this->attendanceService->resolveStateForDate($emp4, Carbon::today(), $att4);
        $this->assertEquals('late', $state4['status']);
    }

    /**
     * Regression Test for Bug 2 (Weekly Off Priority over Future)
     */
    public function test_future_sundays_resolve_as_weekly_off(): void
    {
        $dept = Department::create([
            'name' => 'Standard',
            'shift_start' => '09:30:00',
            'shift_end' => '17:30:00',
            'grace_minutes' => 5,
        ]);
        $emp = User::factory()->create(['department_id' => $dept->id]);

        // Find a Sunday in the future
        $futureSunday = Carbon::today()->addWeek()->next(Carbon::SUNDAY);

        // Resolve status for future Sunday
        $state = $this->attendanceService->resolveStateForDate($emp, $futureSunday);
        
        // Assert that status is weekly off ('off') instead of 'future'
        $this->assertEquals('off', $state['status']);

        // Find a Monday in the future
        $futureMonday = Carbon::today()->addWeek()->next(Carbon::MONDAY);
        $stateMonday = $this->attendanceService->resolveStateForDate($emp, $futureMonday);

        // Assert that a normal weekday in the future resolves to 'future'
        $this->assertEquals('future', $stateMonday['status']);
    }

    /**
     * Regression Test for Bug 3 (Unplanned Leave Balance Validation)
     */
    public function test_unplanned_leave_balance_validation(): void
    {
        $emp = User::factory()->create(['leave_balance' => 5.00]);

        $this->actingAs($emp);

        // 1. Submit 6 days unplanned leave (exceeds balance) -> Should throw exception / fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient leave balance');

        \App\Services\LeaveBalanceService::applyRequest($emp, [
            'leave_type' => 'unplanned',
            'start_date' => Carbon::today()->subDays(5)->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'), // 6 days
            'reason' => 'Family emergency',
        ]);
    }

    /**
     * Regression Test for Admin Auto-Approval
     */
    public function test_admin_leave_requests_are_auto_approved(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'leave_balance' => 10.00,
        ]);
        $this->actingAs($admin);

        // 1. Submit planned leave (should auto approve)
        $response = $this->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(3)->format('Y-m-d'), // 2 days
            'reason' => 'Conference',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $admin->id,
            'leave_type' => 'planned',
            'status' => 'approved',
            'approver_id' => $admin->id,
        ]);

        // Balance should change immediately
        $admin->refresh();
        $this->assertEquals(8.00, $admin->leave_balance);
    }

    /**
     * Regression Test for Bug 7 (Approved Planned Leave Rejection flow)
     */
    public function test_approved_planned_leave_rejection_restores_balance_and_updates_all_modules(): void
    {
        $dept = Department::create([
            'name' => 'Engineering',
            'shift_start' => '09:30:00',
            'shift_end' => '17:30:00',
            'grace_minutes' => 5,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'leave_balance' => 5.00,
            'department_id' => $dept->id,
        ]);
        $employee->payrollProfile()->create([
            'base_salary' => 30000.00,
            'payroll_enabled' => true,
            'salary_effective_date' => Carbon::today()->startOfMonth(),
        ]);

        $dateStr = Carbon::today()->subDays(2)->format('Y-m-d');
        $date = Carbon::parse($dateStr);

        // 1. Create and Approve Leave Request
        $this->actingAs($employee);
        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'start_date' => $dateStr,
            'end_date' => $dateStr,
            'total_days' => 1.0,
            'reason' => 'Vacation day',
            'status' => 'pending',
            'is_paid' => true,
        ]);

        $this->actingAs($admin);
        \App\Services\LeaveBalanceService::approveRequest($leaveRequest, $admin, 'Approved.');

        $employee->refresh();
        $this->assertEquals(4.00, $employee->leave_balance);

        // Assert attendance state resolves as planned leave
        $stateBefore = $this->attendanceService->resolveStateForDate($employee, $date, null, $leaveRequest->fresh());
        $this->assertEquals('planned', $stateBefore['status']);
        $this->assertEquals(0.0, $stateBefore['salary_deduction']);

        // 2. Reject the approved leave request
        \App\Services\LeaveBalanceService::rejectRequest($leaveRequest, $admin, 'Actually rejected.');

        $employee->refresh();
        // Leave balance must be restored
        $this->assertEquals(5.00, $employee->leave_balance);

        // Resolved attendance state must become absent
        $stateAfter = $this->attendanceService->resolveStateForDate($employee, $date, null, $leaveRequest->fresh());
        $this->assertEquals('absent', $stateAfter['status']);
        $this->assertEquals(1.0, $stateAfter['salary_deduction']);

        // Calendar day status resolves to absent
        $gridDays = $this->attendanceService->getAttendanceStatesForRange($employee, $date, $date);
        $this->assertEquals('absent', $gridDays[$dateStr]['status']);

        // Payroll calculation must reflect absent and apply salary deduction
        $payroll = app(PayrollService::class)->calculateMonthlyPayroll($employee, $date->year, $date->month);
        $this->assertEquals('absent', $payroll['daily_breakdown'][$dateStr]['status']);
        $this->assertEquals(1.0, $payroll['daily_breakdown'][$dateStr]['deduction_factor']);

        // Audit Trail check
        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leaveRequest->id,
            'to_status' => 'rejected',
            'action' => 'rejected',
            'user_id' => $admin->id,
        ]);
    }

    /**
     * Regression Test for Bug 8 (Attendance Log status filter integration)
     */
    public function test_attendance_log_filters_integration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dept = Department::create([
            'name' => 'Sales',
            'shift_start' => '09:30:00',
            'shift_end' => '17:30:00',
            'grace_minutes' => 5,
        ]);
        $emp1 = User::factory()->create(['name' => 'Alex', 'department_id' => $dept->id]);
        $emp2 = User::factory()->create(['name' => 'Blake', 'department_id' => $dept->id]);

        // emp1 checked in on time
        Carbon::setTestNow(Carbon::today()->setTime(9, 30, 0));
        Attendance::create([
            'user_id' => $emp1->id,
            'date' => Carbon::today(),
            'check_in_time' => Carbon::today()->setTime(9, 30, 0),
            'status' => 'present',
        ]);

        // emp2 has approved planned leave
        $leave = LeaveRequest::create([
            'user_id' => $emp2->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today(),
            'total_days' => 1,
            'status' => 'approved',
            'is_paid' => true,
            'reason' => 'Vacation',
        ]);

        $this->actingAs($admin);

        // Filter by status 'present'
        $response1 = $this->get(route('admin.attendance.logs', [
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => 'present'
        ]));
        $response1->assertStatus(200);
        $response1->assertSee('Alex');
        $response1->assertDontSee('Blake');

        // Filter by status 'planned' (Planned Leave)
        $response2 = $this->get(route('admin.attendance.logs', [
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => 'planned'
        ]));
        $response2->assertStatus(200);
        $response2->assertDontSee('Alex');
        $response2->assertSee('Blake');
    }
}
