<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Test employee login redirecting to password change first, then access to dashboard.
     */
    public function test_employee_must_change_password_flow(): void
    {
        // 1. Create employee with must_change_password default true
        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_id' => 'EMP00001',
            'status' => 'active',
            'phone' => '+123456789',
            'joining_date' => '2026-06-10',
            'must_change_password' => true
        ]);

        // 2. Login as employee - verify redirect to /password/change because of CheckPasswordChange middleware
        $loginResponse = $this->post('/login', [
            'email' => $employee->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($employee);
        // Should redirect to password.change
        $loginResponse->assertRedirect(route('password.change', absolute: false));

        // 3. Attempting to access dashboard directly should redirect to password change screen
        $directDashboardResponse = $this->actingAs($employee)->get('/employee/dashboard');
        $directDashboardResponse->assertRedirect(route('password.change', absolute: false));

        // 4. Access password change screen successfully
        $changeScreenResponse = $this->actingAs($employee)->get(route('password.change'));
        $changeScreenResponse->assertStatus(200);
        $changeScreenResponse->assertSee('Please set a new secure password to access your account.');

        // 5. Change password successfully
        $changeResponse = $this->actingAs($employee)->post(route('password.change.update'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $changeResponse->assertRedirect(route('employee.dashboard', absolute: false));
        
        $employee->refresh();
        $this->assertFalse($employee->must_change_password);

        // 6. Access employee dashboard and verify phone, joining date, and other profile details are visible
        $dashboardResponse = $this->actingAs($employee)->get('/employee/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('EMP00001');
        $dashboardResponse->assertSee('+123456789');
        $dashboardResponse->assertSee('Jun 10, 2026');
    }

    /**
     * Test manager/admin flows for employee provisioning (auto-gen password vs manual override).
     */
    public function test_manager_admin_provisioning_flow(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_id' => 'ADM00001',
            'status' => 'active',
            'must_change_password' => false
        ]);

        $department = Department::create([
            'name' => 'Support',
            'code' => 'SUP',
            'description' => 'Customer support'
        ]);

        // 1. Provision employee - verify it assigns DEFAULT_EMPLOYEE_PASSWORD from config
        $defaultPassword = config('employees.default_employee_password');
        $autoGenResponse = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Auto Provisioned',
            'email' => 'auto@example.com',
            'role' => 'employee',
            'status' => 'active',
            'phone' => '111-222-333',
            'joining_date' => '2026-06-10',
            'department_id' => $department->id,
        ]);

        $autoGenResponse->assertRedirect(route('employees.index'));
        // Verify credentials flashed in session match the default password
        $autoGenResponse->assertSessionHas('success_provisioned');
        $provisionedData = session('success_provisioned');
        $this->assertEquals('Auto Provisioned', $provisionedData['name']);
        $this->assertEquals('EMP00001', $provisionedData['employee_id']);
        $this->assertEquals($defaultPassword, $provisionedData['password']);
        
        $newEmployee1 = User::where('email', 'auto@example.com')->first();
        $this->assertNotNull($newEmployee1);
        $this->assertEquals('EMP00001', $newEmployee1->employee_id);
        $this->assertTrue($newEmployee1->must_change_password);
        // Verify hashed password matches DEFAULT_EMPLOYEE_PASSWORD
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($defaultPassword, $newEmployee1->password));

        // 2. Try passing manual password parameters and verify they are IGNORED, assigning DEFAULT_EMPLOYEE_PASSWORD instead
        $manualResponse = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Manual Ignored',
            'email' => 'manual@example.com',
            'role' => 'employee',
            'status' => 'active',
            'password' => 'someCustomPassword123',
            'password_confirmation' => 'someCustomPassword123',
            'phone' => '444-555-666',
            'joining_date' => '2026-06-10',
            'department_id' => $department->id,
        ]);

        $manualResponse->assertRedirect(route('employees.index'));
        $manualResponse->assertSessionHas('success_provisioned');
        $provisionedData2 = session('success_provisioned');
        $this->assertEquals($defaultPassword, $provisionedData2['password']);

        $newEmployee2 = User::where('email', 'manual@example.com')->first();
        $this->assertNotNull($newEmployee2);
        $this->assertEquals('EMP00002', $newEmployee2->employee_id);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($defaultPassword, $newEmployee2->password));
    }

    /**
     * Test manager/admin monitoring dashboard and details view.
     */
    public function test_manager_monitoring_and_access_control(): void
    {
        // 1. Create Department
        $dept = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        // 2. Create Manager
        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // 3. Create Employee
        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $dept->id,
            'must_change_password' => false,
            'manager_id' => $manager->id,
        ]);

        // 4. Employee attempts to access manager dashboard - gets redirected
        $empDashboardResponse = $this->actingAs($employee)->get('/dashboard');
        $empDashboardResponse->assertRedirect(route('employee.dashboard'));

        // 5. Employee attempts to access employee detail view - gets 403 Forbidden
        $empDetailResponse = $this->actingAs($employee)->get(route('admin.attendance.employee.show', $employee));
        $empDetailResponse->assertStatus(403);

        // 6. Manager accesses dashboard successfully
        $mgrDashboardResponse = $this->actingAs($manager)->get('/dashboard');
        $mgrDashboardResponse->assertStatus(200);
        $mgrDashboardResponse->assertSee('Manager Attendance Dashboard');
        $mgrDashboardResponse->assertSee($employee->name);

        // 7. Employee checks in and out
        // Create an attendance record manually for the employee
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'date' => today(),
            'check_in_time' => now()->subHours(8),
            'check_out_time' => now(),
            'status' => 'present'
        ]);

        // 8. Manager accesses dashboard and sees check in/out and employee details
        $mgrDashboardWithActivity = $this->actingAs($manager)->get('/dashboard');
        $mgrDashboardWithActivity->assertStatus(200);
        $mgrDashboardWithActivity->assertSee($employee->name);
        $mgrDashboardWithActivity->assertSee('checked in');
        $mgrDashboardWithActivity->assertSee('checked out');

        // 9. Manager accesses filtered dashboard
        $filteredResponse = $this->actingAs($manager)->get('/dashboard?department_id=' . $dept->id . '&search=' . urlencode($employee->name));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee($employee->name);

        // 10. Manager accesses employee detail view successfully
        $mgrDetailResponse = $this->actingAs($manager)->get(route('admin.attendance.employee.show', $employee));
        $mgrDetailResponse->assertStatus(200);
        $mgrDetailResponse->assertSee($employee->name);
        $mgrDetailResponse->assertSee('Last 30 Days Statistics');
        $mgrDetailResponse->assertSee('Present Days');
    }

    /**
     * Test Phase D.5: Self attendance features for all roles (Employee, Manager, Admin).
     */
    public function test_self_attendance_access_and_flow(): void
    {
        $department = Department::create([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
            'department_id' => $department->id,
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
            'department_id' => $department->id,
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
            'department_id' => $department->id,
        ]);

        // 1. Verify access control: Admin can access `/my-attendance`
        $adminResponse = $this->actingAs($admin)->get(route('attendance.my-attendance'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('My Attendance');
        $adminResponse->assertSee('Last 30 Days Statistics');
        $adminResponse->assertSee($admin->name);

        // 2. Verify access control: Manager can access `/my-attendance`
        $managerResponse = $this->actingAs($manager)->get(route('attendance.my-attendance'));
        $managerResponse->assertStatus(200);
        $managerResponse->assertSee('My Attendance');
        $managerResponse->assertSee($manager->name);

        // 3. Verify access control: Employee can access `/my-attendance`
        $empResponse = $this->actingAs($employee)->get(route('attendance.my-attendance'));
        $empResponse->assertStatus(200);
        $empResponse->assertSee('My Attendance');
        $empResponse->assertSee($employee->name);

        // 4. Test Check-in and Check-out flow for a Manager
        $this->actingAs($manager);
        
        // Assert no attendance record yet
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $manager->id,
            'date' => today()->format('Y-m-d 00:00:00'),
        ]);

        // Check in
        $checkInResponse = $this->post(route('attendance.check-in'));
        $checkInResponse->assertRedirect();
        
        // Assert attendance record exists and status is calculated
        $this->assertDatabaseHas('attendances', [
            'user_id' => $manager->id,
            'date' => today()->format('Y-m-d 00:00:00'),
        ]);

        $attendance = Attendance::where('user_id', $manager->id)->where('date', today())->first();
        $this->assertNotNull($attendance->check_in_time);
        $this->assertNull($attendance->check_out_time);

        // Visit `/my-attendance` and check statistics rendering and page layout
        $myAttendanceView = $this->get(route('attendance.my-attendance'));
        $myAttendanceView->assertStatus(200);
        $myAttendanceView->assertSee('✓ Check Out'); // Check out form is available
        $myAttendanceView->assertSee('Days Present');
        $myAttendanceView->assertSee('Total Hours');

        // Check out
        $checkOutResponse = $this->post(route('attendance.check-out'));
        $checkOutResponse->assertRedirect();

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_time);

        // Visit view again and verify checked-out state
        $myAttendanceViewCheckedOut = $this->get(route('attendance.my-attendance'));
        $myAttendanceViewCheckedOut->assertSee('✓ Checked in and out for today');
    }
}

