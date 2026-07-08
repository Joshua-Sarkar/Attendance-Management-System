<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $employee1;
    protected User $employee2;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-04 12:00:00'); // Saturday

        $this->department = Department::create([
            'name' => 'Engineering',
            'shift_start_time' => '09:00:00',
            'shift_end_time' => '17:30:00',
            'grace_minutes' => 15,
        ]);

        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $this->manager = User::create([
            'employee_id' => 'MGR001',
            'name' => 'Manager User',
            'email' => 'manager@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);

        $this->employee1 = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Employee One',
            'email' => 'emp1@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
            'manager_id' => $this->manager->id,
        ]);

        $this->employee2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Employee Two',
            'email' => 'emp2@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function unauthenticated_users_cannot_access_calendar_data()
    {
        $response = $this->getJson(route('attendance.calendar.data'));
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_employee_can_access_their_own_calendar_data()
    {
        $response = $this->actingAs($this->employee1)
            ->getJson(route('attendance.calendar.data'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'gridDays',
                'departments',
                'employee' => ['name', 'dept', 'id'],
                'metrics' => [
                    'attendanceRate',
                    'presentDays',
                    'lateDays',
                    'halfDays',
                    'absentDays',
                    'leaveDays',
                    'avgCheckIn',
                    'avgCheckOut',
                    'avgHoursWorked',
                    'totalHoursWorked',
                    'overtime',
                    'lateMinutes',
                    'earlyExitMinutes',
                    'overrideCount',
                    'payrollEligibleDays',
                ]
            ]);
    }

    /** @test */
    public function employee_cannot_access_another_employees_calendar_data()
    {
        $response = $this->actingAs($this->employee1)
            ->getJson(route('attendance.calendar.data', ['user_id' => $this->employee2->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function manager_can_access_assigned_employee_calendar_data_but_not_unassigned()
    {
        // Assigned
        $response = $this->actingAs($this->manager)
            ->getJson(route('attendance.calendar.data', ['user_id' => $this->employee1->id]));
        $response->assertStatus(200);

        // Unassigned
        $response = $this->actingAs($this->manager)
            ->getJson(route('attendance.calendar.data', ['user_id' => $this->employee2->id]));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_any_employee_calendar_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('attendance.calendar.data', ['user_id' => $this->employee2->id]));
        $response->assertStatus(200);
    }

    /** @test */
    public function it_filters_by_month_and_year_navigation()
    {
        // July 2026 is month = 6 (0-indexed)
        $response = $this->actingAs($this->employee1)
            ->getJson(route('attendance.calendar.data', ['month' => 6, 'year' => 2026]));

        $response->assertStatus(200);
        $data = $response->json();

        // 42 days grid length
        $this->assertCount(42, $data['gridDays']);

        // First day of July 2026 is a Wednesday. Monday of that week is June 29.
        $this->assertEquals('2026-06-29', $data['gridDays'][0]['iso']);
        $this->assertFalse($data['gridDays'][0]['inRange']);

        // Wednesday is July 1st
        $this->assertEquals('2026-07-01', $data['gridDays'][2]['iso']);
        $this->assertTrue($data['gridDays'][2]['inRange']);
    }

    /** @test */
    public function it_filters_by_custom_date_range()
    {
        $response = $this->actingAs($this->employee1)
            ->getJson(route('attendance.calendar.data', [
                'start_date' => '2026-07-05',
                'end_date' => '2026-07-15'
            ]));

        $response->assertStatus(200);
        $data = $response->json();

        // July 5 is Sunday. July 15 is Wednesday.
        // startOfWeek of July 5 is June 29.
        // endOfWeek of July 15 is July 19.
        // June 29 to July 19 is 21 days (3 weeks).
        $this->assertCount(21, $data['gridDays']);

        // June 29
        $this->assertEquals('2026-06-29', $data['gridDays'][0]['iso']);
        $this->assertFalse($data['gridDays'][0]['inRange']);

        // July 5
        $this->assertEquals('2026-07-05', $data['gridDays'][6]['iso']);
        $this->assertTrue($data['gridDays'][6]['inRange']);

        // July 15
        $this->assertEquals('2026-07-15', $data['gridDays'][16]['iso']);
        $this->assertTrue($data['gridDays'][16]['inRange']);

        // July 16
        $this->assertEquals('2026-07-16', $data['gridDays'][17]['iso']);
        $this->assertFalse($data['gridDays'][17]['inRange']);
    }

    /** @test */
    public function it_calculates_metrics_correctly_for_selected_range()
    {
        // Setup some records for July 2026:
        // July 1 (Wednesday) - Present
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-07-01',
            'check_in_time' => '2026-07-01 09:00:00',
            'check_out_time' => '2026-07-01 17:30:00', // 8.5 hours
            'status' => 'present',
            'classification' => 'full_day',
        ]);

        // July 2 (Thursday) - Late (grace limit is 09:15)
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-07-02',
            'check_in_time' => '2026-07-02 09:20:00', // 20 mins late, grace ends 09:15, so 5 mins late past grace
            'check_out_time' => '2026-07-02 17:30:00', // 8.16 hours
            'status' => 'late',
            'classification' => 'full_day',
        ]);

        // July 3 (Friday) - Approved Leave
        LeaveRequest::create([
            'user_id' => $this->employee1->id,
            'leave_type' => 'planned',
            'start_date' => '2026-07-03',
            'end_date' => '2026-07-03',
            'total_days' => 1,
            'status' => 'approved',
            'is_paid' => true,
            'reason' => 'Annual Leave',
        ]);

        // Query July 1 to July 3 (3 days)
        $response = $this->actingAs($this->employee1)
            ->getJson(route('attendance.calendar.data', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-03'
            ]));

        $response->assertStatus(200);
        $data = $response->json();
        $metrics = $data['metrics'];

        // Present = 1, Half = 1, Leave = 1. Off = 0.
        // Total working days = 3 (none are Sunday).
        // Attendance % = (1 present + 0 late + 1 half + 0 wfh) / 3 working days = 67%
        $this->assertEquals('67%', $metrics['attendanceRate']);
        $this->assertEquals(1, $metrics['presentDays']);
        $this->assertEquals(0, $metrics['lateDays']);
        $this->assertEquals(1, $metrics['halfDays']);
        $this->assertEquals(0, $metrics['absentDays']);
        $this->assertEquals(1, $metrics['leaveDays']);

        // Check worked hours
        // Day 1 hours = 8.5
        // Day 2 hours = 8.16 (approx 8.2)
        // Day 3 hours = 0.0 (on leave)
        // Total worked hours = 8.5 + 8.2 = 16.7
        // Worked days = 2
        // Avg hours = 16.7 / 2 = 8.35 -> 8.4h
        $this->assertEquals('8.4h', $metrics['avgHoursWorked']);
        $this->assertEquals('16.7h', $metrics['totalHoursWorked']);

        // Late Minutes:
        // July 2 check-in at 09:20. Grace threshold ends at 09:15.
        // So 09:20 vs 09:15 is 5 late minutes.
        $this->assertEquals('5m', $metrics['lateMinutes']);
    }
}
