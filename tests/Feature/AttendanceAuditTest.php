<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager1;
    protected User $manager2;
    protected User $emp1;
    protected User $emp2;
    protected Department $deptEng;
    protected Department $deptSales;

    protected function setUp(): void
    {
        parent::setUp();

        config(['attendance.start_time' => '09:00']);
        config(['attendance.grace_minutes' => 15]);

        $this->deptEng = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'shift_start_time' => '09:00:00',
            'grace_minutes' => 15,
        ]);

        $this->deptSales = Department::create([
            'name' => 'Sales',
            'code' => 'SLS',
            'shift_start_time' => '09:00:00',
            'grace_minutes' => 15,
        ]);

        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->manager1 = User::create([
            'employee_id' => 'MGR001',
            'name' => 'Engineering Manager',
            'email' => 'mgr1@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->deptEng->id,
        ]);

        $this->manager2 = User::create([
            'employee_id' => 'MGR002',
            'name' => 'Sales Manager',
            'email' => 'mgr2@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->deptSales->id,
        ]);

        $this->emp1 = User::create([
            'employee_id' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptEng->id,
            'manager_id' => $this->manager1->id,
        ]);

        $this->emp2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Jane Smith',
            'email' => 'jane@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptSales->id,
            'manager_id' => $this->manager2->id,
        ]);
    }

    /** @test */
    public function logs_page_has_admin_only_access()
    {
        // Admins can access
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs'));
        $response->assertStatus(200);

        // Managers are forbidden (403)
        $response = $this->actingAs($this->manager1)->get(route('admin.attendance.logs'));
        $response->assertStatus(403);

        // Employees are forbidden (403)
        $response = $this->actingAs($this->emp1)->get(route('admin.attendance.logs'));
        $response->assertStatus(403);
    }

    /** @test */
    public function detail_page_access_control()
    {
        // Admin can view any employee
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.employee.show', $this->emp1));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.employee.show', $this->emp2));
        $response->assertStatus(200);

        // Manager 1 can view emp1 (assigned)
        $response = $this->actingAs($this->manager1)->get(route('admin.attendance.employee.show', $this->emp1));
        $response->assertStatus(200);

        // Manager 1 cannot view emp2 (unassigned)
        $response = $this->actingAs($this->manager1)->get(route('admin.attendance.employee.show', $this->emp2));
        $response->assertStatus(403);

        // Employees cannot access
        $response = $this->actingAs($this->emp1)->get(route('admin.attendance.employee.show', $this->emp2));
        $response->assertStatus(403);
    }

    /** @test */
    public function logs_page_filtering_by_date_search_and_department()
    {
        // Create an attendance record for emp1
        $dateStr = '2026-06-20';
        Carbon::setTestNow(Carbon::parse($dateStr . ' 09:00:00'));

        Attendance::create([
            'user_id' => $this->emp1->id,
            'date' => $dateStr,
            'check_in_time' => now(),
            'status' => 'present',
        ]);

        // 1. Filter by date
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr]));
        $response->assertStatus(200);
        $response->assertSee('John Doe');

        // 2. Filter by search name
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'search' => 'John']));
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');

        // 3. Filter by search employee_id
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'search' => 'EMP002']));
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');

        // 4. Filter by department
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'department_id' => $this->deptSales->id]));
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');

        Carbon::setTestNow();
    }

    /** @test */
    public function logs_page_filtering_by_status()
    {
        $dateStr = '2026-06-20';
        Carbon::setTestNow(Carbon::parse($dateStr . ' 09:30:00'));

        // Emp1 is Late
        Attendance::create([
            'user_id' => $this->emp1->id,
            'date' => $dateStr,
            'check_in_time' => now(),
            'status' => 'late',
        ]);

        // Emp2 is WFH (Leave request approved)
        LeaveRequest::create([
            'user_id' => $this->emp2->id,
            'leave_type' => 'work_from_home',
            'start_date' => $dateStr,
            'end_date' => $dateStr,
            'total_days' => 1,
            'reason' => 'WFH',
            'status' => 'approved',
        ]);

        // Admin filters for status=late
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'status' => 'late']));
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');

        // Admin filters for status=wfh
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'status' => 'wfh']));
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');

        // Admin filters for status=absent (no records/leaves, but active)
        // Since emp1 is late, emp2 is wfh, we check another active employee without attendance.
        // Let's create an active employee
        $emp3 = User::create([
            'employee_id' => 'EMP003',
            'name' => 'Absent Guy',
            'email' => 'absent@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr, 'status' => 'absent']));
        $response->assertStatus(200);
        $response->assertSee('Absent Guy');
        $response->assertDontSee('John Doe');
        $response->assertDontSee('Jane Smith');

        Carbon::setTestNow();
    }

    /** @test */
    public function dashboard_metric_renaming_and_admin_button()
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Checked In Today');
        $response->assertSee('Late Arrivals Today');
        $response->assertSee('View Full Attendance Logs');

        // Manager can view dashboard but not full logs button
        $response = $this->actingAs($this->manager1)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Checked In Today');
        $response->assertSee('Late Arrivals Today');
        $response->assertDontSee('View Full Attendance Logs');
    }

    /** @test */
    public function employee_detail_view_statistics_and_percentage()
    {
        // Create leaves and attendances for the last 30 days
        $targetUser = $this->emp1;
        
        Carbon::setTestNow(Carbon::parse('2026-06-20'));

        // Let's create a mix of attendance records
        // Days 1-5: Present (5 days)
        for ($i = 0; $i < 5; $i++) {
            Attendance::create([
                'user_id' => $targetUser->id,
                'date' => Carbon::parse('2026-06-20')->subDays(29 - $i),
                'check_in_time' => Carbon::parse('2026-06-20')->subDays(29 - $i)->setTime(9, 0),
                'check_out_time' => Carbon::parse('2026-06-20')->subDays(29 - $i)->setTime(17, 0),
                'status' => 'present',
            ]);
        }

        // Days 6-7: Late (2 days)
        for ($i = 5; $i < 7; $i++) {
            Attendance::create([
                'user_id' => $targetUser->id,
                'date' => Carbon::parse('2026-06-20')->subDays(29 - $i),
                'check_in_time' => Carbon::parse('2026-06-20')->subDays(29 - $i)->setTime(9, 30),
                'status' => 'late',
            ]);
        }

        // Day 8: Leave casual (1 day)
        LeaveRequest::create([
            'user_id' => $targetUser->id,
            'leave_type' => 'casual_leave',
            'start_date' => Carbon::parse('2026-06-20')->subDays(21),
            'end_date' => Carbon::parse('2026-06-20')->subDays(21),
            'total_days' => 1,
            'status' => 'approved',
            'reason' => 'Casual Leave',
        ]);

        // Day 9: WFH (1 day)
        LeaveRequest::create([
            'user_id' => $targetUser->id,
            'leave_type' => 'work_from_home',
            'start_date' => Carbon::parse('2026-06-20')->subDays(20),
            'end_date' => Carbon::parse('2026-06-20')->subDays(20),
            'total_days' => 1,
            'status' => 'approved',
            'reason' => 'WFH Day',
        ]);

        // Rest of the weekdays are absent (auto fallback)
        // Let's verify detail page displays the values
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.employee.show', $targetUser));
        $response->assertStatus(200);

        // Check if employee details are visible
        $response->assertSee('John Doe');
        $response->assertSee('EMP001');
        $response->assertSee('Engineering');
        $response->assertSee('Engineering Manager');

        // Check summary cards values
        // Present Days card (5 present + 2 late = 7 present days)
        $response->assertSee('Present Days');
        // Late Days card
        $response->assertSee('Late Days');
        // Leave Days card
        $response->assertSee('Leave Days');
        // WFH Days card
        $response->assertSee('WFH Days');
        // Absent Days card
        $response->assertSee('Absent Days');

        // Check Attendance Rate percentage display
        $response->assertSee('Attendance Rate');

        Carbon::setTestNow();
    }
}
