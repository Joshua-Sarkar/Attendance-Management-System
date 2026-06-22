<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordStrategySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'IT Support',
            'code' => 'ITD',
        ]);

        $this->admin = User::create([
            'employee_id' => 'ADM00010',
            'name' => 'System Admin',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
            'must_change_password' => false,
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP00010',
            'name' => 'Standard Employee',
            'email' => 'employee@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
            'must_change_password' => false,
        ]);
        
        // Ensure DEFAULT_EMPLOYEE_PASSWORD is set in config/env
        config(['app.default_employee_password' => 'asdfghjkl']);
        putenv('DEFAULT_EMPLOYEE_PASSWORD=asdfghjkl');
    }

    /** @test */
    public function it_fails_creation_if_default_employee_password_is_missing()
    {
        // 1. Unset the DEFAULT_EMPLOYEE_PASSWORD environment and server variables
        $oldEnvVal = $_ENV['DEFAULT_EMPLOYEE_PASSWORD'] ?? null;
        $oldServerVal = $_SERVER['DEFAULT_EMPLOYEE_PASSWORD'] ?? null;
        unset($_ENV['DEFAULT_EMPLOYEE_PASSWORD']);
        unset($_SERVER['DEFAULT_EMPLOYEE_PASSWORD']);
        putenv('DEFAULT_EMPLOYEE_PASSWORD');

        $employeeData = [
            'name' => 'No Pass Employee',
            'email' => 'nopass@example.com',
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
        ];

        // 2. Attempt manual employee creation
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), $employeeData);

        // 3. Verify it is redirected back with error validation keys
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'nopass@example.com',
        ]);

        // Restore env
        if ($oldEnvVal !== null) {
            $_ENV['DEFAULT_EMPLOYEE_PASSWORD'] = $oldEnvVal;
        }
        if ($oldServerVal !== null) {
            $_SERVER['DEFAULT_EMPLOYEE_PASSWORD'] = $oldServerVal;
        }
        putenv('DEFAULT_EMPLOYEE_PASSWORD=asdfghjkl');
    }

    /** @test */
    public function it_allows_admin_to_reset_employee_password_to_default()
    {
        $defaultPassword = env('DEFAULT_EMPLOYEE_PASSWORD', 'asdfghjkl');

        // Verify pre-conditions
        $this->assertFalse($this->employee->must_change_password);

        // 1. Post to the admin password reset endpoint
        $response = $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employee));

        // 2. Verify redirect back with success message
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. Verify the employee fields are updated
        $this->employee->refresh();
        $this->assertTrue($this->employee->must_change_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($defaultPassword, $this->employee->password));
    }

    /** @test */
    public function it_forces_password_reset_flow_after_admin_reset()
    {
        $defaultPassword = env('DEFAULT_EMPLOYEE_PASSWORD', 'asdfghjkl');

        // 1. Admin resets the password
        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employee));

        // Refresh employee instance so it is marked as must_change_password in memory
        $this->employee->refresh();

        // Logout the admin so the employee can login guest-middleware-free
        $this->post('/logout');

        // 2. Employee attempts login with default password
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => $defaultPassword,
        ]);

        $this->assertAuthenticatedAs($this->employee);

        // 3. Verify they are immediately redirected to password change screen
        $response = $this->get('/employee/dashboard');
        $response->assertRedirect(route('password.change', absolute: false));

        // 4. Update to a new password
        $updateResponse = $this->actingAs($this->employee)->post(route('password.change.update'), [
            'password' => 'new-secure-password123',
            'password_confirmation' => 'new-secure-password123',
        ]);

        $updateResponse->assertRedirect(route('employee.dashboard', absolute: false));

        // 5. Verify the flag is now false
        $this->employee->refresh();
        $this->assertFalse($this->employee->must_change_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secure-password123', $this->employee->password));
    }
}
