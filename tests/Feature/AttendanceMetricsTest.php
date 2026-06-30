<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday

        // Ensure we load config values
        config(['attendance.start_time' => '09:00']);
        config(['attendance.grace_minutes' => 15]);

        $this->department = Department::create([
            'name' => 'Engineering',
            'shift_start_time' => '09:00:00',
            'grace_minutes' => 15,
        ]);

        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'System Admin',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Standard Employee',
            'email' => 'employee@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_correct_late_minutes_and_assigns_status()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Scenario 1: Check-in at 09:00 (Present, 0 mins late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $att1 = $attendanceService->checkIn($this->employee);
        $this->assertEquals('present', $att1->status);
        $this->assertEquals(0, $att1->late_minutes);

        // Reset check-in
        $att1->delete();

        // Scenario 2: Check-in at 09:10 (Present, 0 mins late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 10, 0));
        $att2 = $attendanceService->checkIn($this->employee);
        $this->assertEquals('present', $att2->status);
        $this->assertEquals(0, $att2->late_minutes);

        $att2->delete();

        // Scenario 3: Check-in at 09:15 (Present, 0 mins late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 15, 0));
        $att3 = $attendanceService->checkIn($this->employee);
        $this->assertEquals('present', $att3->status);
        $this->assertEquals(0, $att3->late_minutes);

        $att3->delete();

        // Scenario 4: Check-in at 09:16 (Late, 1 min late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 16, 0));
        $att4 = $attendanceService->checkIn($this->employee);
        $this->assertEquals('late', $att4->status);
        $this->assertEquals(1, $att4->late_minutes);

        $att4->delete();

        // Scenario 5: Check-in at 09:30 (Late, 15 mins late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 30, 0));
        $att5 = $attendanceService->checkIn($this->employee);
        $this->assertEquals('late', $att5->status);
        $this->assertEquals(15, $att5->late_minutes);

        // Cleanup
        Carbon::setTestNow();
    }

    /** @test */
    public function present_today_includes_late_employees()
    {
        $employee2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Late Employee',
            'email' => 'late_emp@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);
        $targetDateStr = Carbon::today()->format('Y-m-d');

        // Check in employee 1 at 09:00 (present)
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $attendanceService->checkIn($this->employee);

        // Check in employee 2 at 09:30 (late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 30, 0));
        $attendanceService->checkIn($employee2);

        $stats = $attendanceService->getTodayStats($targetDateStr, null, $this->admin);

        // Present Today should be 2 (includes both present and late)
        $this->assertEquals(2, $stats['present']);
        // Late Today should be 1
        $this->assertEquals(1, $stats['late']);
        // Average late minutes should be 15 (only one late employee checked in for 15 mins late)
        $this->assertEquals(15.0, $stats['average_late_minutes']);

        // Check exceptions list compiles correctly
        $this->assertCount(1, $stats['exceptions']['late']);
        $this->assertEquals('Late Employee', $stats['exceptions']['late'][0]['name']);
        $this->assertEquals(15, $stats['exceptions']['late'][0]['late_minutes']);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_correctly_compiles_attendance_exceptions_widget_stats()
    {
        $employeeOnLeave = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Leave Employee',
            'email' => 'leave_emp@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $employeeWFH = User::create([
            'employee_id' => 'EMP003',
            'name' => 'WFH Employee',
            'email' => 'wfh_emp@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $targetDate = Carbon::today();
        $targetDateStr = $targetDate->format('Y-m-d');

        // 1. Create leave request
        LeaveRequest::create([
            'user_id' => $employeeOnLeave->id,
            'leave_type' => 'casual_leave',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Leave.',
            'status' => 'approved',
        ]);

        // 2. Create WFH request
        LeaveRequest::create([
            'user_id' => $employeeWFH->id,
            'leave_type' => 'work_from_home',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'WFH.',
            'status' => 'approved',
        ]);

        // 3. Create Late check-in
        Carbon::setTestNow(Carbon::today()->setTime(9, 30, 0));
        $attendanceService = resolve(\App\Services\AttendanceService::class);
        $attendanceService->checkIn($this->employee);

        $stats = $attendanceService->getTodayStats($targetDateStr, null, $this->admin);

        // Verify counts
        $this->assertEquals(1, $stats['on_leave']);
        $this->assertEquals(1, $stats['wfh']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(1, $stats['present']); // Standard employee is late, so 1 present (which is late)

        // Verify exceptions details
        $this->assertCount(1, $stats['exceptions']['on_leave']);
        $this->assertEquals('Leave Employee', $stats['exceptions']['on_leave'][0]['name']);

        $this->assertCount(1, $stats['exceptions']['wfh']);
        $this->assertEquals('WFH Employee', $stats['exceptions']['wfh'][0]['name']);

        $this->assertCount(1, $stats['exceptions']['late']);
        $this->assertEquals('Standard Employee', $stats['exceptions']['late'][0]['name']);
        $this->assertEquals(15, $stats['exceptions']['late'][0]['late_minutes']);

        Carbon::setTestNow();
    }
}
