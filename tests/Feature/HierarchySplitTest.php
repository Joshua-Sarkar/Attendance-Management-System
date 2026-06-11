<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchySplitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Managers can view departments but cannot modify them.
     */
    public function test_department_access_control(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $dept = Department::create([
            'name' => 'HR Department',
            'code' => 'HRD',
        ]);

        // 1. Admin can access departments list and modification screens
        $response = $this->actingAs($admin)->get(route('departments.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('departments.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->post(route('departments.store'), [
            'name' => 'Engineering Dept',
            'code' => 'ENGD',
        ]);
        $response->assertRedirect(route('departments.index'));

        // 2. Manager can view departments list
        $response = $this->actingAs($manager)->get(route('departments.index'));
        $response->assertStatus(200);

        // 3. Manager is forbidden from creating/modifying/deleting departments
        $response = $this->actingAs($manager)->get(route('departments.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($manager)->post(route('departments.store'), [
            'name' => 'Marketing Dept',
            'code' => 'MKTG',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($manager)->get(route('departments.edit', $dept));
        $response->assertStatus(403);

        $response = $this->actingAs($manager)->put(route('departments.update', $dept), [
            'name' => 'Updated HR',
            'code' => 'HRD',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($manager)->delete(route('departments.destroy', $dept));
        $response->assertStatus(403);
    }

    /**
     * Test: Admins can create Admins.
     */
    public function test_admin_can_create_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $dept = Department::create([
            'name' => 'IT Support',
            'code' => 'ITS',
        ]);

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $dept->id,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    }

    /**
     * Test: Managers cannot create Admins or Managers.
     */
    public function test_manager_cannot_create_admin_or_manager(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $dept = Department::create([
            'name' => 'IT Support',
            'code' => 'ITS',
        ]);

        // Attempt to create an Admin
        $response = $this->actingAs($manager)->post(route('employees.store'), [
            'name' => 'Fake Admin',
            'email' => 'fakeadmin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $dept->id,
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', [
            'email' => 'fakeadmin@example.com',
        ]);

        // Attempt to create a Manager
        $response = $this->actingAs($manager)->post(route('employees.store'), [
            'name' => 'Fake Manager',
            'email' => 'fakemanager@example.com',
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $dept->id,
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', [
            'email' => 'fakemanager@example.com',
        ]);
    }

    /**
     * Test: Admin users cannot be assigned another Admin.
     */
    public function test_admin_cannot_be_assigned_another_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $anotherAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $dept = Department::create([
            'name' => 'Finance',
            'code' => 'FIN',
        ]);

        // When creating an admin and trying to pass admin_id, it should be saved as null
        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Admin Test User',
            'email' => 'admintest@example.com',
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $dept->id,
            'admin_id' => $anotherAdmin->id,
        ]);

        $response->assertRedirect(route('employees.index'));
        $createdUser = User::where('email', 'admintest@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->admin_id);
        $this->assertNull($createdUser->manager_id);
    }

    /**
     * Test: Workforce metrics show ONLY active users.
     */
    public function test_workforce_metrics_active_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // Active Manager
        User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // Active Employee
        User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // Inactive Employee
        User::factory()->create([
            'role' => 'employee',
            'status' => 'inactive',
            'must_change_password' => false,
        ]);

        // Resigned Manager
        User::factory()->create([
            'role' => 'manager',
            'status' => 'resigned',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Metrics: People in Company = 3, Admins = 1, Managers = 1, Employees = 1
        $response->assertSee('People in Company');
        $companyMetrics = $response->viewData('companyMetrics');

        $this->assertEquals(3, $companyMetrics['people_in_company']);
        $this->assertEquals(1, $companyMetrics['admins']);
        $this->assertEquals(1, $companyMetrics['managers']);
        $this->assertEquals(1, $companyMetrics['employees']);
    }

    /**
     * Test: Unassigned employees remain visible to Admins, invisible to Managers.
     */
    public function test_unassigned_employees_visibility(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
            'name' => 'Unassigned Person',
            'manager_id' => null,
        ]);

        // Admin sees the unassigned employee on the dashboard
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertSee('Unassigned Person');

        // Manager does NOT see the unassigned employee on the dashboard
        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertDontSee('Unassigned Person');
    }

    /**
     * Test: Managers only see employees assigned to them.
     */
    public function test_managers_only_see_assigned_employees(): void
    {
        $manager1 = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $manager2 = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $employee1 = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
            'name' => 'Employee One',
            'manager_id' => $manager1->id,
        ]);

        $employee2 = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
            'name' => 'Employee Two',
            'manager_id' => $manager2->id,
        ]);

        // Manager 1 sees Employee One, not Employee Two
        $response = $this->actingAs($manager1)->get(route('dashboard'));
        $response->assertSee('Employee One');
        $response->assertDontSee('Employee Two');

        // Manager 2 sees Employee Two, not Employee One
        $response = $this->actingAs($manager2)->get(route('dashboard'));
        $response->assertSee('Employee Two');
        $response->assertDontSee('Employee One');
    }
}
