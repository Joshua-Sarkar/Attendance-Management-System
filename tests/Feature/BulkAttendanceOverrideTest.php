<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkAttendanceOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee1;
    protected User $employee2;
    protected Department $deptEng;
    protected Department $deptHr;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday

        // Setup departments
        $this->deptEng = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'shift_start_time' => '09:30:00',
            'shift_end_time' => '17:30:00',
            'grace_minutes' => 5,
        ]);

        $this->deptHr = Department::create([
            'name' => 'HR',
            'code' => 'HRD',
            'shift_start_time' => '10:00:00',
            'shift_end_time' => '18:00:00',
            'grace_minutes' => 5,
        ]);

        // Setup users
        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->employee1 = User::create([
            'employee_id' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptEng->id,
            'leave_balance' => 10.0,
        ]);

        $this->employee2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Jane Smith',
            'email' => 'jane@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptHr->id,
            'leave_balance' => 5.0,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function preview_endpoint_correctly_calculates_affected_counts_and_existing_records()
    {
        // 1. Create an existing override for employee1 on 2026-06-25
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'wfh',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_reason' => 'Previous reason',
        ]);

        // 2. Create an approved leave request for employee2 covering 2026-06-26
        LeaveRequest::create([
            'user_id' => $this->employee2->id,
            'leave_type' => 'casual_leave',
            'start_date' => '2026-06-26',
            'end_date' => '2026-06-26',
            'total_days' => 1,
            'reason' => 'Personal work',
            'status' => 'approved',
        ]);

        // 3. Post preview payload targeting both employees for 2026-06-25 and 2026-06-26
        $response = $this->actingAs($this->admin)->postJson(route('admin.attendance.override.preview'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id, $this->employee2->id],
            'date_mode' => 'range',
            'start_date' => '2026-06-25',
            'end_date' => '2026-06-26',
            'working_days_only' => false,
            'skip_leaves' => false,
            'skip_overrides' => false,
            'status' => 'present',
            'classification' => 'automatic',
            'override_reason' => 'Bulk override preview testing',
            'conflict_handling' => 'cancel',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'employees_selected' => 2,
            'dates_selected' => 2,
            'attendance_records_affected' => 4,
            'existing_overrides' => 1,
            'existing_leave_records' => 1,
            'records_that_will_change' => 4,
            'has_conflicts' => true,
        ]);
        $this->assertStringContainsString('Error: 2 conflict(s) detected', $response->json('conflict_message'));
    }

    /** @test */
    public function preview_respects_skip_filters()
    {
        // Create an existing override for employee1 on 2026-06-25
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'wfh',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_reason' => 'Previous reason',
        ]);

        // Post preview with skip_overrides = true
        $response = $this->actingAs($this->admin)->postJson(route('admin.attendance.override.preview'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id],
            'date_mode' => 'single',
            'date' => '2026-06-25',
            'skip_overrides' => true,
            'status' => 'present',
            'classification' => 'automatic',
            'override_reason' => 'Skip check testing',
            'conflict_handling' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'employees_selected' => 1,
            'dates_selected' => 1,
            'existing_overrides' => 1,
            'records_that_will_change' => 0, // Since it is skipped
            'has_conflicts' => false, // conflicts are handled by skip setting
        ]);
    }

    /** @test */
    public function apply_overrides_to_entire_department_with_date_range()
    {
        // 2026-06-25 is Thursday, 2026-06-26 is Friday, 2026-06-27 is Saturday, 2026-06-28 is Sunday (weekend off)
        // Engineering department has John Doe (employee1). HR department has Jane Smith (employee2).

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'department',
            'department_ids' => [$this->deptEng->id],
            'date_mode' => 'range',
            'start_date' => '2026-06-25',
            'end_date' => '2026-06-28',
            'working_days_only' => true, // skips Sunday (June 28)
            'include_sundays' => false,
            'skip_leaves' => false,
            'skip_overrides' => false,
            'status' => 'wfh',
            'classification' => 'full_day',
            'override_reason' => 'Engineering department working from home range',
            'conflict_handling' => 'cancel',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Employee 1 should have overrides for 25th, 26th, and 27th (Saturday is a working day according to WorkingDaysTest)
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'wfh',
            'is_overridden' => true,
        ]);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-27 00:00:00',
            'status' => 'wfh',
            'is_overridden' => true,
        ]);

        // Sunday June 28 should not be overridden since working_days_only = true and include_sundays = false
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-28 00:00:00',
        ]);

        // Employee 2 should have no overrides since she is in HR department
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->employee2->id,
            'date' => '2026-06-25 00:00:00',
        ]);
    }

    /** @test */
    public function apply_overrides_to_multiple_specific_dates()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id, $this->employee2->id],
            'date_mode' => 'multiple',
            'dates' => ['2026-06-25', '2026-06-29'],
            'status' => 'absent',
            'classification' => 'full_day',
            'override_reason' => 'Multiple dates override',
            'conflict_handling' => 'cancel',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'absent',
            'is_overridden' => true,
        ]);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee2->id,
            'date' => '2026-06-29 00:00:00',
            'status' => 'absent',
            'is_overridden' => true,
        ]);
    }

    /** @test */
    public function database_transaction_rolls_back_if_any_conflict_handling_cancel_occurs()
    {
        // 1. Create existing override on 2026-06-25 for employee1
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'wfh',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_reason' => 'Existing override',
        ]);

        // 2. Perform bulk override targeting both on 2026-06-25, conflict strategy is cancel
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id, $this->employee2->id],
            'date_mode' => 'single',
            'date' => '2026-06-25',
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'Try to apply override',
            'conflict_handling' => 'cancel',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['override_reason']);

        // Verify transaction rolled back: employee2 should not have any record created
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->employee2->id,
            'date' => '2026-06-25 00:00:00',
        ]);

        // Employee1 should still have their original 'wfh' status
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'wfh',
        ]);
    }

    /** @test */
    public function apply_half_day_mapping_to_present_status()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id],
            'date_mode' => 'single',
            'date' => '2026-06-25',
            'status' => 'half_day',
            'classification' => 'half_day',
            'override_reason' => 'Testing half day UI abstraction',
            'conflict_handling' => 'cancel',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Must persist present status and half_day classification internally
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => '2026-06-25 00:00:00',
            'status' => 'present',
            'classification' => 'half_day',
            'is_overridden' => true,
        ]);
    }

    /** @test */
    public function audit_trail_verifications()
    {
        // 1. Setup a historical override record (no metadata)
        $historicalDate = '2026-06-20';
        $historicalTime = Carbon::parse('2026-06-20 12:00:00');
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => $historicalDate . ' 00:00:00',
            'status' => 'present',
            'classification' => 'full_day',
            'is_overridden' => true,
            'overridden_by' => $this->admin->id,
            'overridden_at' => $historicalTime,
            'override_reason' => 'Historical manual correction',
            'override_type' => 'bulk',
        ]);

        // Verify historical override is loaded in view correctly with fallback mappings
        $response1 = $this->actingAs($this->admin)->get(route('admin.attendance.logs'));
        $response1->assertStatus(200);
        $grouped1 = $response1->viewData('groupedOverrides');
        
        $this->assertNotEmpty($grouped1);
        $historicalGroup = collect($grouped1)->first(fn($g) => $g['reason'] === 'Historical manual correction');
        $this->assertNotNull($historicalGroup);
        $this->assertEquals('replace', $historicalGroup['conflict_strategy']); // fallback
        $this->assertEquals($historicalDate, $historicalGroup['dates_affected']); // fallback
        $this->assertEquals(1, $historicalGroup['records_modified']);

        // 2. Perform a new bulk override affecting multiple dates and employees
        $newTime = Carbon::parse('2026-06-25 15:00:00');
        Carbon::setTestNow($newTime);

        $response2 = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'scope_type' => 'employee',
            'employee_ids' => [$this->employee1->id, $this->employee2->id],
            'date_mode' => 'range',
            'start_date' => '2026-06-25',
            'end_date' => '2026-06-26',
            'working_days_only' => false,
            'skip_leaves' => false,
            'skip_overrides' => false,
            'status' => 'wfh',
            'classification' => 'full_day',
            'override_reason' => 'Testing new audit logs logic',
            'conflict_handling' => 'skip',
        ]);

        $response2->assertRedirect();

        // Verify the database contains the records and metadata is ONLY on the first record
        $attendances = Attendance::where('override_reason', 'Testing new audit logs logic')->get();
        $this->assertCount(4, $attendances); // 2 employees * 2 dates

        // Only one record should have non-null metadata
        $recordsWithMetadata = $attendances->filter(fn($a) => !is_null($a->metadata));
        $this->assertCount(1, $recordsWithMetadata);
        
        $meta = $recordsWithMetadata->first()->metadata;
        $this->assertEquals('skip', $meta['conflict_strategy']);
        $this->assertEquals(['2026-06-25', '2026-06-26'], $meta['dates_affected']);
        $this->assertEquals(2, $meta['employees_count']);
        $this->assertEquals(4, $meta['records_modified']);

        // Verify new override generates exactly one audit group in the view
        $response3 = $this->actingAs($this->admin)->get(route('admin.attendance.logs'));
        $response3->assertStatus(200);
        $grouped3 = $response3->viewData('groupedOverrides');
        
        $newGroup = collect($grouped3)->first(fn($g) => $g['reason'] === 'Testing new audit logs logic');
        $this->assertNotNull($newGroup);
        $this->assertEquals('skip', $newGroup['conflict_strategy']);
        $this->assertEquals('2026-06-25, 2026-06-26', $newGroup['dates_affected']);
        $this->assertEquals(4, $newGroup['records_modified']);
        $this->assertEquals(2, $newGroup['affected_count']);

        // 3. Verify Override dropdown options in response html
        $html = $response3->getContent();
        // Option values
        $this->assertStringContainsString('value="planned">Planned Leave</option>', $html);
        $this->assertStringContainsString('value="upa">Unplanned Approved (UPA)</option>', $html);
        
        Carbon::setTestNow(); // reset
    }
}
