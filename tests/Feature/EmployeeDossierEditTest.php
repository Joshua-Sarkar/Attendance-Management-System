<?php

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\PayrollProfile;
use App\Models\EmployeeTimelineEntry;
use App\Models\ProfileCorrectionRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    
    // Create departments
    $this->adminDept = Department::create(['name' => 'Administration', 'code' => 'ADMIN']);
    $this->techDept = Department::create(['name' => 'Technology', 'code' => 'TECH']);

    // Create Admin
    $this->admin = User::factory()->create([
        'employee_id' => 'ADM-001',
        'role' => 'admin',
        'department_id' => $this->adminDept->id,
        'status' => 'active',
    ]);

    // Create Manager
    $this->manager = User::factory()->create([
        'employee_id' => 'MGR-001',
        'role' => 'manager',
        'department_id' => $this->techDept->id,
        'status' => 'active',
    ]);

    // Create Employee reporting to Manager
    $this->employee = User::factory()->create([
        'employee_id' => 'EMP-001',
        'role' => 'employee',
        'department_id' => $this->techDept->id,
        'manager_id' => $this->manager->id,
        'status' => 'active',
    ]);
});

test('administrator can edit employee identity and upload profile photo', function () {
    $this->actingAs($this->admin);

    $photo = UploadedFile::fake()->create('avatar.png', 100, 'image/png');

    $response = $this->put(route('employees.update', $this->employee), [
        'employee_id' => 'EMP-TEST-99',
        'name' => 'Updated Name',
        'email' => 'updated.email@example.com',
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'father_name' => 'Father Name Test',
        'mother_name' => 'Mother Name Test',
        'profile_photo' => $photo,
    ]);

    $response->assertRedirect(route('employees.index'));
    $response->assertSessionHas('success');

    $this->employee->refresh();
    expect($this->employee->name)->toBe('Updated Name');
    expect($this->employee->email)->toBe('updated.email@example.com');
    expect($this->employee->employeeProfile->father_name)->toBe('Father Name Test');
    expect($this->employee->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->employee->profile_photo_path);
});

test('administrator can edit employee category and other employment profile fields', function () {
    $this->actingAs($this->admin);

    $response = $this->put(route('employees.update', $this->employee), [
        'employee_id' => $this->employee->employee_id,
        'name' => $this->employee->name,
        'email' => $this->employee->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'designation' => 'Senior Developer',
        'employee_category' => 'Engineering Staff',
    ]);

    $response->assertRedirect(route('employees.index'));
    $this->employee->refresh();
    expect($this->employee->employeeProfile->designation)->toBe('Senior Developer');
    expect($this->employee->employeeProfile->employee_category)->toBe('Engineering Staff');
});

test('manager demotion with direct reports requires action selection', function () {
    $this->actingAs($this->admin);

    // Try to demote manager without specifying replacement or confirm clear
    $response = $this->put(route('employees.update', $this->manager), [
        'employee_id' => $this->manager->employee_id,
        'name' => $this->manager->name,
        'email' => $this->manager->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee', // demotion
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors(['role']);
    
    // Demote manager with confirm clear hierarchy
    $response = $this->put(route('employees.update', $this->manager), [
        'employee_id' => $this->manager->employee_id,
        'name' => $this->manager->name,
        'email' => $this->manager->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'confirm_clear_hierarchy' => 1,
    ]);

    $response->assertRedirect(route('employees.index'));
    $this->employee->refresh();
    expect($this->employee->manager_id)->toBeNull();
});

test('administrator can edit payroll and leave balances and remaining leave is calculated', function () {
    $this->actingAs($this->admin);

    // Initial balances
    $this->employee->leaveBalance()->delete();
    $this->employee->leaveBalance()->create([
        'planned_leave' => 5.0,
        'unplanned_leave' => 2.0,
        'remaining_leave' => 7.0,
    ]);

    $response = $this->put(route('employees.update', $this->employee), [
        'employee_id' => $this->employee->employee_id,
        'name' => $this->employee->name,
        'email' => $this->employee->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'base_salary' => 50000,
        'salary_effective_date' => '2026-07-01',
        'payroll_enabled' => 1,
        'planned_leave' => 10.0,
        'unplanned_leave' => 5.0,
        'paternity_leave' => 2.0,
        'maternity_leave' => 0.0,
        'compensatory_leave' => 1.0,
        'carry_forward' => 3.0,
        'utilized_leave' => 4.0,
    ]);

    $response->assertRedirect(route('employees.index'));
    
    $this->employee->refresh();
    
    // Verify payroll profile
    expect((float)$this->employee->payrollProfile->base_salary)->toBe(50000.0);
    expect($this->employee->payrollProfile->payroll_enabled)->toBeTrue();

    // Verify calculated remaining leave: 10 + 5 + 2 + 0 + 1 + 3 - 4 = 17
    $lb = $this->employee->leaveBalance;
    expect((float)$lb->planned_leave)->toBe(10.0);
    expect((float)$lb->unplanned_leave)->toBe(5.0);
    expect((float)$lb->remaining_leave)->toBe(17.0);
    expect((float)$this->employee->leave_balance)->toBe(17.0);

    // Verify ledger entry
    $ledger = $this->employee->leaveLedgerEntries()->where('type', 'adjustment')->first();
    expect($ledger)->not->toBeNull();
    expect($ledger->description)->toContain('Administrative adjustment');
});

test('administrator can edit birthday leave credit if it exists', function () {
    $this->actingAs($this->admin);

    // Set employee date of birth so they have eligible profile
    $this->employee->employeeProfile()->updateOrCreate([], [
        'date_of_birth' => '1990-07-10',
        'joining_date' => '2026-06-01',
    ]);
    
    $this->employee->refresh();

    // Force-sync birthday credits so a credit exists for this year
    $this->employee->syncBirthdayCredits(\Carbon\Carbon::parse('2026-07-09'));

    $year = now()->year;
    $credit = \App\Models\LeaveCredit::where('user_id', $this->employee->id)
        ->where('source_identifier', "birthday_{$year}")
        ->first();
    
    expect($credit)->not->toBeNull();
    expect((float)$credit->used_amount)->toBe(0.0);

    // Now edit Birthday Leave balance to 0.0 (used/taken)
    $response = $this->put(route('employees.update', $this->employee), [
        'employee_id' => $this->employee->employee_id,
        'name' => $this->employee->name,
        'email' => $this->employee->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'birthday_leave' => 0.0,
    ]);

    $response->assertRedirect(route('employees.index'));

    $credit->refresh();
    // It should set used_amount to 1.0 (amount - balance => 1.0 - 0.0 = 1.0)
    expect((float)$credit->used_amount)->toBe(1.0);

    // Verify ledger entry logs the birthday leave update
    $ledger = $this->employee->leaveLedgerEntries()
        ->where('type', 'adjustment')
        ->where('description', 'like', '%Birthday Leave updated from 1 to 0%')
        ->first();
    expect($ledger)->not->toBeNull();
});

test('administrator can add, edit, and delete manual timeline entries', function () {
    $this->actingAs($this->admin);

    // 1. Store
    $response = $this->post(route('admin.timeline.store', $this->employee), [
        'title' => 'Manual Milestone',
        'description' => 'Milestone Description',
        'entry_date' => '2026-07-15',
    ]);

    $response->assertRedirect();
    $entry = EmployeeTimelineEntry::where('user_id', $this->employee->id)->first();
    expect($entry)->not->toBeNull();
    expect($entry->title)->toBe('Manual Milestone');
    expect($entry->entry_date->format('Y-m-d'))->toBe('2026-07-15');

    // 2. Update
    $response = $this->put(route('admin.timeline.update', $entry), [
        'title' => 'Updated Milestone',
        'description' => 'Updated Description',
        'entry_date' => '2026-07-20',
    ]);

    $response->assertRedirect();
    $entry->refresh();
    expect($entry->title)->toBe('Updated Milestone');
    expect($entry->entry_date->format('Y-m-d'))->toBe('2026-07-20');

    // 3. Delete
    $response = $this->delete(route('admin.timeline.destroy', $entry));
    $response->assertRedirect();
    expect(EmployeeTimelineEntry::find($entry->id))->toBeNull();
});

test('administrator can add, edit, and delete corrections with reasons', function () {
    $this->actingAs($this->admin);

    // 1. Store
    $response = $this->post(route('admin.corrections.store', $this->employee), [
        'field' => 'Phone Number',
        'message' => 'Incorrect phone number recorded', // Reason/message is required
        'status' => 'resolved',
        'admin_note' => 'Corrected phone number',
    ]);

    $response->assertRedirect();
    $req = ProfileCorrectionRequest::where('user_id', $this->employee->id)->first();
    expect($req)->not->toBeNull();
    expect($req->field)->toBe('Phone Number');
    expect($req->status)->toBe('resolved');

    // 2. Update
    $response = $this->put(route('admin.corrections.update', $req), [
        'field' => 'Personal Email',
        'message' => 'Email correction requested',
        'status' => 'pending',
        'admin_note' => 'Pending review',
    ]);

    $response->assertRedirect();
    $req->refresh();
    expect($req->field)->toBe('Personal Email');
    expect($req->status)->toBe('pending');

    // 3. Delete
    $response = $this->delete(route('admin.corrections.destroy', $req));
    $response->assertRedirect();
    expect(ProfileCorrectionRequest::find($req->id))->toBeNull();
});

test('administrator can edit and persist independent remaining and pending leave balances', function () {
    $this->actingAs($this->admin);

    // Initial balances
    $this->employee->leaveBalance()->delete();
    $this->employee->leaveBalance()->create([
        'planned_leave' => 5.0,
        'unplanned_leave' => 2.0,
        'pending_leave' => 1.0,
        'remaining_leave' => 7.0,
    ]);

    $response = $this->put(route('employees.update', $this->employee), [
        'employee_id' => $this->employee->employee_id,
        'name' => $this->employee->name,
        'email' => $this->employee->email,
        'department_id' => $this->techDept->id,
        'role' => 'employee',
        'status' => 'active',
        'base_salary' => 50000,
        'salary_effective_date' => '2026-07-01',
        'payroll_enabled' => 1,
        'planned_leave' => 10.0,
        'unplanned_leave' => 5.0,
        'paternity_leave' => 2.0,
        'maternity_leave' => 0.0,
        'compensatory_leave' => 1.0,
        'carry_forward' => 3.0,
        'utilized_leave' => 4.0,
        'pending_leave' => 3.5,
        'remaining_leave' => 25.0, // Explicitly different from calculated (17.0)
    ]);

    $response->assertRedirect(route('employees.index'));
    
    $this->employee->refresh();
    
    $lb = $this->employee->leaveBalance;
    expect((float)$lb->planned_leave)->toBe(10.0);
    expect((float)$lb->unplanned_leave)->toBe(5.0);
    expect((float)$lb->pending_leave)->toBe(3.5);
    expect((float)$lb->remaining_leave)->toBe(25.0);
    expect((float)$this->employee->leave_balance)->toBe(25.0);

    // Verify ledger entry logs the adjustment
    $ledger = $this->employee->leaveLedgerEntries()->where('type', 'adjustment')->first();
    expect($ledger)->not->toBeNull();
    expect($ledger->description)->toContain('Remaining leave updated from 7 to 25');
    expect($ledger->description)->toContain('Pending leave updated from 1 to 3.5');
});

