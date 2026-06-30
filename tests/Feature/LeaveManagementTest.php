<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestLog;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $otherManager;
    protected User $employee;
    protected User $unrelatedEmployee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday

        $this->department = Department::create(['name' => 'Engineering']);

        // Create Users conforming to AMS Phase D.6 rules
        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'System Admin',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
            'leave_balance' => 10.00,
        ]);

        $this->manager = User::create([
            'employee_id' => 'MGR001',
            'name' => 'Direct Manager',
            'email' => 'manager@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->department->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);

        $this->otherManager = User::create([
            'employee_id' => 'MGR002',
            'name' => 'Other Manager',
            'email' => 'other_manager@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->department->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Standard Employee',
            'email' => 'employee@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
            'manager_id' => $this->manager->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);

        $this->unrelatedEmployee = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Unrelated Employee',
            'email' => 'unrelated@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
            'manager_id' => $this->otherManager->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function employee_can_submit_leave_request_successfully()
    {
        $startDate = Carbon::today()->addDays(5)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(7)->format('Y-m-d');

        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Recovery from fever.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'status' => 'pending',
            'total_days' => 3,
            'reason' => 'Recovery from fever.',
        ]);

        $leave = LeaveRequest::first();
        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'from_status' => null,
            'to_status' => 'pending',
            'action' => 'applied',
            'user_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function assigned_manager_can_approve_employee_leave_request()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Family event.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->post(route('leaves.approve', $leave), [
            'notes' => 'Approved, have a nice time.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertEquals($this->manager->id, $leave->fresh()->approver_id);

        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'from_status' => 'pending',
            'to_status' => 'approved',
            'action' => 'approved',
            'notes' => 'Approved, have a nice time.',
            'user_id' => $this->manager->id,
        ]);
    }

    /** @test */
    public function assigned_manager_can_reject_employee_leave_request()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Family event.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->post(route('leaves.reject', $leave), [
            'rejection_reason' => 'Project delivery week.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertEquals('rejected', $leave->fresh()->status);

        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'from_status' => 'pending',
            'to_status' => 'rejected',
            'action' => 'rejected',
            'notes' => 'Project delivery week.',
            'user_id' => $this->manager->id,
        ]);
    }

    /** @test */
    public function non_assigned_manager_cannot_approve_employee_leave_request()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Family event.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->otherManager)->post(route('leaves.approve', $leave));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_override_any_decision()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Personal trip.',
            'status' => 'approved',
            'approver_id' => $this->manager->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('leaves.override', $leave), [
            'override_status' => 'rejected',
            'override_notes' => 'Administrative override due to roster updates.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertEquals('rejected', $leave->fresh()->status);
        $this->assertEquals($this->admin->id, $leave->fresh()->approver_id);

        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'from_status' => 'approved',
            'to_status' => 'rejected',
            'action' => 'overridden',
            'notes' => 'Administrative override due to roster updates.',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function managers_cannot_approve_their_own_requests()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->manager->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Manager break.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)->post(route('leaves.approve', $leave), [
            'notes' => 'Self approve notes',
        ]);
        $response->assertSessionHas('error', 'You cannot approve your own leave request.');
    }

    /** @test */
    public function manager_request_must_be_approved_by_admin()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->manager->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3,
            'reason' => 'Manager break.',
            'status' => 'pending',
        ]);

        // Other managers cannot approve
        $response1 = $this->actingAs($this->otherManager)->post(route('leaves.approve', $leave));
        $response1->assertStatus(403);

        // Admin can approve
        $response2 = $this->actingAs($this->admin)->post(route('leaves.approve', $leave), [
            'notes' => 'Approved for management tier.',
        ]);
        $response2->assertRedirect(route('leaves.index'));
        $this->assertEquals('approved', $leave->fresh()->status);
    }

    /** @test */
    public function admin_can_create_paid_leave_and_receive_automatic_approval_with_deduction()
    {
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(4)->format('Y-m-d');

        $originalBalance = $this->admin->leave_balance;

        $response = $this->actingAs($this->admin)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Urgent matter.',
        ]);

        $response->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->admin->id,
            'leave_type' => 'planned',
            'status' => 'approved',
            'approver_id' => $this->admin->id,
        ]);

        $this->assertEquals($originalBalance - 3, $this->admin->fresh()->leave_balance);

        $leave = LeaveRequest::where('user_id', $this->admin->id)->first();
        // Assert audit trail records applied & approved
        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'action' => 'applied',
        ]);
        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'action' => 'approved',
        ]);
    }

    /** @test */
    public function admin_can_create_unplanned_leave_and_receive_automatic_approval_with_deduction()
    {
        $startDate = Carbon::today()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(4)->format('Y-m-d');

        $originalBalance = $this->admin->leave_balance;

        $response = $this->actingAs($this->admin)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Urgent matter.',
        ]);

        $response->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->admin->id,
            'leave_type' => 'unplanned',
            'status' => 'approved',
            'approver_id' => $this->admin->id,
        ]);

        $this->assertEquals($originalBalance - 3, $this->admin->fresh()->leave_balance);
    }

    /** @test */
    public function start_date_cannot_be_in_the_past()
    {
        $pastDate = Carbon::yesterday()->format('Y-m-d');
        $futureDate = Carbon::today()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $pastDate,
            'end_date' => $futureDate,
            'reason' => 'Sick.',
        ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    /** @test */
    public function end_date_cannot_be_before_start_date()
    {
        $startDate = Carbon::today()->addDays(4)->format('Y-m-d');
        $endDate = Carbon::today()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Sick.',
        ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    /** @test */
    public function overlapping_leave_requests_are_blocked()
    {
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(7),
            'total_days' => 3,
            'reason' => 'Original request.',
            'status' => 'approved',
        ]);

        // Attempting to apply on overlapping range: 6th to 8th
        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(6)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(8)->format('Y-m-d'),
            'reason' => 'Overlapping.',
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $this->assertEquals(1, LeaveRequest::where('user_id', $this->employee->id)->count());
    }

    /** @test */
    public function cancelled_requests_do_not_block_future_leaves()
    {
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(7),
            'total_days' => 3,
            'reason' => 'Original request.',
            'status' => 'cancelled',
        ]);

        // Overlapping date range is fine since the previous is cancelled
        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(6)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(8)->format('Y-m-d'),
            'reason' => 'Not blocked.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertEquals(2, LeaveRequest::where('user_id', $this->employee->id)->count());
    }

    /** @test */
    public function employees_can_cancel_their_own_pending_or_approved_leaves()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(7),
            'total_days' => 3,
            'reason' => 'Trip.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->employee)->post(route('leaves.cancel', $leave));
        $response->assertRedirect(route('leaves.index'));
        $this->assertEquals('cancelled', $leave->fresh()->status);

        $this->assertDatabaseHas('leave_request_logs', [
            'leave_request_id' => $leave->id,
            'from_status' => 'approved',
            'to_status' => 'cancelled',
            'action' => 'cancelled',
            'user_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function approved_leave_integrates_dynamically_with_attendance()
    {
        $targetDate = Carbon::today()->addDays(3)->format('Y-m-d');

        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Trip.',
            'status' => 'approved',
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);
        $employees = $attendanceService->getFilteredAttendance($targetDate, null, null, $this->manager);
        
        $employeeRecord = $employees->firstWhere('id', $this->employee->id);
        
        $this->assertNotNull($employeeRecord->today_attendance);
        $this->assertEquals('on_leave', $employeeRecord->today_attendance->status);
    }

    /** @test */
    public function physical_check_in_overrides_approved_leave_status()
    {
        $targetDate = Carbon::today();
        $targetDateStr = $targetDate->format('Y-m-d');

        // Create approved leave for today
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Trip.',
            'status' => 'approved',
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Before check-in: Should resolve as on_leave
        $recordBefore = $attendanceService->getTodayAttendance($this->employee);
        $this->assertEquals('on_leave', $recordBefore->status);

        // Check in physically
        $attendanceService->checkIn($this->employee);

        // After check-in: Should find DB attendance record (status present/late) overriding the leave
        $recordAfter = $attendanceService->getTodayAttendance($this->employee);
        $this->assertNotNull($recordAfter->check_in_time);
        $this->assertContains($recordAfter->status, ['present', 'late']);
    }

    /** @test */
    public function attendance_overrides_leave_in_dashboard_metrics_and_workforce_details()
    {
        $targetDate = Carbon::today();
        $targetDateStr = $targetDate->format('Y-m-d');

        // Create approved leave for today
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Trip.',
            'status' => 'approved',
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Before check-in: Dashboard metrics counts 1 on_leave, 0 present
        $statsBefore = $attendanceService->getTodayStats($targetDateStr, null, $this->manager);
        $this->assertEquals(1, $statsBefore['on_leave']);
        $this->assertEquals(0, $statsBefore['present']);

        // Check in
        $attendanceService->checkIn($this->employee);

        // After check-in: Dashboard metrics counts 0 on_leave, 1 present
        $statsAfter = $attendanceService->getTodayStats($targetDateStr, null, $this->manager);
        $this->assertEquals(0, $statsAfter['on_leave']);
        $this->assertEquals(1, $statsAfter['present']);

        // Workforce Attendance Details should display attendance status (present/late) rather than on_leave
        $employees = $attendanceService->getFilteredAttendance($targetDateStr, null, null, $this->manager);
        $employeeRecord = $employees->firstWhere('id', $this->employee->id);
        $this->assertNotNull($employeeRecord->today_attendance);
        $this->assertContains($employeeRecord->today_attendance->status, ['present', 'late']);
    }

    /** @test */
    public function attendance_overrides_wfh_in_dashboard_metrics_and_workforce_details()
    {
        $targetDate = Carbon::today();
        $targetDateStr = $targetDate->format('Y-m-d');

        // Create approved WFH for today
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'work_from_home',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'WFH today.',
            'status' => 'approved',
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Before check-in: Dashboard metrics counts 1 wfh, 0 present
        $statsBefore = $attendanceService->getTodayStats($targetDateStr, null, $this->manager);
        $this->assertEquals(1, $statsBefore['wfh']);
        $this->assertEquals(0, $statsBefore['present']);

        // Check in
        $attendanceService->checkIn($this->employee);

        // After check-in: Dashboard metrics counts 0 wfh, 1 present
        $statsAfter = $attendanceService->getTodayStats($targetDateStr, null, $this->manager);
        $this->assertEquals(0, $statsAfter['wfh']);
        $this->assertEquals(1, $statsAfter['present']);

        // Workforce Attendance Details should display attendance status rather than wfh
        $employees = $attendanceService->getFilteredAttendance($targetDateStr, null, null, $this->manager);
        $employeeRecord = $employees->firstWhere('id', $this->employee->id);
        $this->assertNotNull($employeeRecord->today_attendance);
        $this->assertContains($employeeRecord->today_attendance->status, ['present', 'late']);
    }
}
