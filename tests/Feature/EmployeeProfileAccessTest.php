<?php

use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\Department;

test('GET /employees/create returns a 200 and renders the create form correctly', function () {
    $department = Department::create([
        'name' => 'HR',
        'code' => 'HRD',
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('employees.create'));

    $response->assertStatus(200);
    $response->assertSee('Add Workforce Member');
});

test('admin can create an employee with profile fields across multiple sections and view them', function () {
    $department = Department::create([
        'name' => 'Engineering',
        'code' => 'ENG',
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $employeeData = [
        'name' => 'John Created',
        'email' => 'created@example.com',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $department->id,
        // Personal section
        'father_name' => 'Create Father',
        'personal_email' => 'personal_created@example.com',
        // Government IDs section
        'aadhar_card' => '1111-2222-3333',
        // Current Address section
        'current_address1' => 'Current Ave 99',
        // Bank Details section
        'bank_name' => 'Chase Bank',
        'account_no' => '1234567890',
        // Experience section
        'previous_year_experience' => '6 Year, 7 Month, 12 Days',
        'years_completed' => '5 Year, 3 Month',
        'overall_year_experience' => '11 Year, 10 Month, 12 Days',
    ];

    $response = $this->actingAs($admin)
        ->post(route('employees.store'), $employeeData);

    $response->assertRedirect(route('employees.index'));

    $newEmployee = User::where('email', 'created@example.com')->first();
    expect($newEmployee)->not->toBeNull();

    $responseView = $this->actingAs($admin)
        ->get(route('employees.show', $newEmployee));

    $responseView->assertStatus(200);
    $responseView->assertSee('John Created');
    $responseView->assertSee('Create Father');
    $responseView->assertSee('personal_created@example.com');
    $responseView->assertSee('1111-2222-3333');
    $responseView->assertSee('Current Ave 99');
    $responseView->assertSee('Chase Bank');
    $responseView->assertSee('1234567890');
    $responseView->assertSee('6 Year, 7 Month, 12 Days');
    $responseView->assertSee('5 Year, 3 Month');
    $responseView->assertSee('11 Year, 10 Month, 12 Days');
});

test('logged-in non-admin employee visiting GET /employees/{their own id} gets a 200, without Edit Profile button', function () {
    $department = Department::create([
        'name' => 'Sales',
        'code' => 'SLS',
    ]);

    $employee = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($employee)
        ->get(route('employees.show', $employee));

    $response->assertStatus(200);
    $response->assertDontSee('Edit Profile');
});

test('logged-in non-admin employee visiting GET /employees/{different user id} gets a 403 response specifically', function () {
    $department = Department::create([
        'name' => 'Finance',
        'code' => 'FIN',
    ]);

    $employee1 = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $employee2 = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($employee1)
        ->get(route('employees.show', $employee2));

    $response->assertStatus(403);
});

test('submitting create form with same_as_current_address checked saves matching permanent_address fields', function () {
    $department = Department::create([
        'name' => 'Marketing',
        'code' => 'MKT',
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $employeeData = [
        'name' => 'Address Mirror',
        'email' => 'mirror@example.com',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $department->id,
        'same_as_current_address' => '1',
        'current_address1' => '789 Current St',
        'current_address2' => 'Suite 10',
        'current_country' => 'USA',
        'current_state' => 'NY',
        'current_city' => 'New York',
        'current_zip' => '10001',
    ];

    $response = $this->actingAs($admin)
        ->post(route('employees.store'), $employeeData);

    $response->assertRedirect(route('employees.index'));

    $newEmployee = User::where('email', 'mirror@example.com')->first();
    expect($newEmployee)->not->toBeNull();
    
    $profile = $newEmployee->employeeProfile;
    expect($profile)->not->toBeNull();
    expect($profile->same_as_current_address)->toBeTrue();
    expect($profile->permanent_address1)->toBe('789 Current St');
    expect($profile->permanent_address2)->toBe('Suite 10');
    expect($profile->permanent_country)->toBe('USA');
    expect($profile->permanent_state)->toBe('NY');
    expect($profile->permanent_city)->toBe('New York');
    expect($profile->permanent_zip)->toBe('10001');
});
