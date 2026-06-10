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

    /**
     * Test the full employee check-in, check-out, and history view flow.
     */
    public function test_employee_flow(): void
    {
        // 1. Create employee
        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_id' => 'EMP-0001',
            'status' => 'active'
        ]);

        // 2. Login as employee and verify redirect to /employee/dashboard
        $loginResponse = $this->post('/login', [
            'email' => $employee->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($employee);
        $loginResponse->assertRedirect(route('employee.dashboard', absolute: false));

        // 3. Verify access to employee dashboard
        $dashboardResponse = $this->actingAs($employee)->get('/employee/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Welcome, ' . $employee->name);

        // 4. Verify check-in works
        $checkInResponse = $this->actingAs($employee)->post(route('attendance.check-in'));
        $checkInResponse->assertRedirect();
        
        $attendance = Attendance::where('user_id', $employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('present', $attendance->status);
        $this->assertNotNull($attendance->check_in_time);
        $this->assertNull($attendance->check_out_time);

        // 5. Verify check-out works
        $checkOutResponse = $this->actingAs($employee)->post(route('attendance.check-out'));
        $checkOutResponse->assertRedirect();

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_time);

        // 6. Verify attendance history displays records
        $historyResponse = $this->actingAs($employee)->get(route('attendance.history'));
        $historyResponse->assertStatus(200);
        $historyResponse->assertSee($employee->name);
        $historyResponse->assertSee('Attendance History');
    }

    /**
     * Test the manager/admin flow.
     */
    public function test_manager_admin_flow(): void
    {
        // 1. Create admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_id' => 'ADM-0001',
            'status' => 'active'
        ]);

        // 2. Login as admin and verify redirect to /dashboard
        $loginResponse = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $loginResponse->assertRedirect(route('dashboard', absolute: false));

        // 3. Verify department CRUD works
        $deptData = [
            'name' => 'Engineering',
            'code' => 'ENG',
            'description' => 'Software engineering team',
        ];

        $deptResponse = $this->actingAs($admin)->post(route('departments.store'), $deptData);
        $deptResponse->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['code' => 'ENG']);

        $department = Department::where('code', 'ENG')->first();

        // 4. Verify employee CRUD works
        $employeeData = [
            'employee_id' => 'EMP-1234',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $department->id,
        ];

        $empResponse = $this->actingAs($admin)->post(route('employees.store'), $employeeData);
        $empResponse->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', ['email' => 'johndoe@example.com']);
    }
}
