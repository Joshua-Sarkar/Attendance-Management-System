<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $employee1;
    protected User $employee2;
    protected Department $deptEng;
    protected Department $deptHr;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday

        // 1. Setup departments with distinct shifts
        $this->deptEng = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'shift_start_time' => '09:30:00',
            'shift_end_time' => '17:30:00',
            'grace_minutes' => 5,
        ]);

        $this->deptHr = Department::create([
            'name' => 'Healthcare',
            'code' => 'HLT',
            'shift_start_time' => '10:00:00',
            'shift_end_time' => '18:00:00',
            'grace_minutes' => 5,
        ]);

        // 2. Setup users
        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->manager = User::create([
            'employee_id' => 'MGR001',
            'name' => 'Manager User',
            'email' => 'manager@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->deptEng->id,
        ]);

        $this->employee1 = User::create([
            'employee_id' => 'EMP001',
            'name' => 'John Doe',
            'email' => 'john@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptEng->id,
            'manager_id' => $this->manager->id,
        ]);

        $this->employee2 = User::create([
            'employee_id' => 'EMP002',
            'name' => 'Jane Smith',
            'email' => 'jane@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->deptHr->id,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function only_admins_can_access_override_endpoint()
    {
        $response = $this->actingAs($this->employee1)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'Approved request',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->manager)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'Approved request',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'Approved request',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function individual_override_creates_or_updates_attendance_correctly()
    {
        $dateStr = '2026-06-29';
        
        // 1. Assert no record initially
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->employee1->id,
            'date' => $dateStr . ' 00:00:00',
        ]);

        // 2. Perform override
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => $dateStr,
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'half_day',
            'override_reason' => 'Admin decided half day present',
        ]);

        $response->assertRedirect();

        // 3. Verify database
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => $dateStr . ' 00:00:00',
            'status' => 'present',
            'classification' => 'half_day',
            'is_overridden' => true,
            'overridden_by' => $this->admin->id,
            'override_reason' => 'Admin decided half day present',
            'override_type' => 'individual',
            'automatic_status' => 'absent',
            'automatic_classification' => 'full_day',
        ]);
    }

    /** @test */
    public function bulk_override_applies_to_multiple_employees()
    {
        $dateStr = '2026-06-28';

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => $dateStr,
            'user_ids' => [$this->employee1->id, $this->employee2->id],
            'status' => 'wfh',
            'classification' => 'full_day',
            'override_reason' => 'Team-wide WFH day',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee1->id,
            'date' => $dateStr . ' 00:00:00',
            'status' => 'wfh',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_type' => 'bulk',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee2->id,
            'date' => $dateStr . ' 00:00:00',
            'status' => 'wfh',
            'classification' => 'full_day',
            'is_overridden' => true,
            'override_type' => 'bulk',
        ]);
    }

    /** @test */
    public function early_check_in_rules()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Employee 1 shift starts at 09:30. Check in early at 09:15
        Carbon::setTestNow(Carbon::today()->setTime(9, 15, 0));
        $att = $attendanceService->checkIn($this->employee1);

        $this->assertEquals('present', $att->status);
        $this->assertEquals('full_day', $att->classification);
        $this->assertEquals(0, $att->late_minutes);

        Carbon::setTestNow();
    }

    /** @test */
    public function late_arrival_rules_trigger_half_day_classification()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Employee 1 shift starts at 09:30, grace period is 5 mins (threshold 09:35)
        // Check in late at 09:36
        Carbon::setTestNow(Carbon::today()->setTime(9, 36, 0));
        $att = $attendanceService->checkIn($this->employee1);

        $this->assertEquals('late', $att->status);
        $this->assertEquals('half_day', $att->classification);
        $this->assertEquals('late_arrival', $att->automatic_classification_reason);
        $this->assertEquals(1, $att->late_minutes);

        Carbon::setTestNow();
    }

    /** @test */
    public function healthcare_department_specific_timings()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // Employee 2 (Healthcare) shift starts at 10:00, grace 5 mins (threshold 10:05)
        // Check in at 09:55 (early present)
        Carbon::setTestNow(Carbon::today()->setTime(9, 55, 0));
        $att1 = $attendanceService->checkIn($this->employee2);
        $this->assertEquals('present', $att1->status);
        $this->assertEquals(0, $att1->late_minutes);
        $att1->delete();

        // Check in at 10:05 (within grace present)
        Carbon::setTestNow(Carbon::today()->setTime(10, 5, 0));
        $att2 = $attendanceService->checkIn($this->employee2);
        $this->assertEquals('present', $att2->status);
        $this->assertEquals(0, $att2->late_minutes);
        $att2->delete();

        // Check in at 10:06 (late, half day)
        Carbon::setTestNow(Carbon::today()->setTime(10, 6, 0));
        $att3 = $attendanceService->checkIn($this->employee2);
        $this->assertEquals('late', $att3->status);
        $this->assertEquals('half_day', $att3->classification);
        $this->assertEquals('late_arrival', $att3->automatic_classification_reason);
        $this->assertEquals(1, $att3->late_minutes);

        Carbon::setTestNow();
    }

    /** @test */
    public function insufficient_hours_does_not_override_late_arrival_reason()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // 1. Check in late at 09:40 -> status late, class half_day, reason late_arrival
        Carbon::setTestNow(Carbon::today()->setTime(9, 40, 0));
        $att = $attendanceService->checkIn($this->employee1);
        $this->assertEquals('late_arrival', $att->automatic_classification_reason);

        // 2. Check out after 1 hour (insufficient hours < 4.0)
        Carbon::setTestNow(Carbon::today()->setTime(10, 40, 0));
        $att = $attendanceService->checkOut($this->employee1);

        // Classification must remain half_day, and reason must remain late_arrival
        $this->assertEquals('half_day', $att->classification);
        $this->assertEquals('late_arrival', $att->automatic_classification_reason);

        Carbon::setTestNow();
    }

    /** @test */
    public function insufficient_hours_triggers_half_day_for_on_time_arrivals()
    {
        $attendanceService = resolve(\App\Services\AttendanceService::class);

        // 1. Check in on time at 09:20 -> status present, class full_day
        Carbon::setTestNow(Carbon::today()->setTime(9, 20, 0));
        $att = $attendanceService->checkIn($this->employee1);
        $this->assertNull($att->automatic_classification_reason);
        $this->assertEquals('full_day', $att->classification);

        // 2. Check out after 3 hours at 12:20 (insufficient hours < 4.0)
        Carbon::setTestNow(Carbon::today()->setTime(12, 20, 0));
        $att = $attendanceService->checkOut($this->employee1);

        // Classification must become half_day, and reason must be insufficient_hours
        $this->assertEquals('half_day', $att->classification);
        $this->assertEquals('insufficient_hours', $att->automatic_classification_reason);

        Carbon::setTestNow();
    }

    /** @test */
    public function override_reason_is_mandatory_for_individual_override()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'full_day',
            // override_reason is missing
        ]);
        $response->assertSessionHasErrors(['override_reason']);

        $response2 = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'full_day',
            'override_reason' => 'abc', // too short
        ]);
        $response2->assertSessionHasErrors(['override_reason']);
    }

    /** @test */
    public function override_reason_is_mandatory_for_bulk_override()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => '2026-06-28',
            'user_ids' => [$this->employee1->id, $this->employee2->id],
            'status' => 'wfh',
            'classification' => 'full_day',
            'override_reason' => '', // empty
        ]);
        $response->assertSessionHasErrors(['override_reason']);
    }

    /** @test */
    public function override_audit_trail_includes_required_fields_and_relations()
    {
        $dateStr = '2026-06-28';

        // Perform override to create audit metadata
        $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => $dateStr,
            'user_id' => $this->employee1->id,
            'status' => 'present',
            'classification' => 'half_day',
            'override_reason' => 'Admin override reason validation',
        ]);

        $attendance = Attendance::where('user_id', $this->employee1->id)
            ->whereDate('date', $dateStr)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertTrue($attendance->is_overridden);
        $this->assertEquals('individual', $attendance->override_type);
        $this->assertEquals('Admin override reason validation', $attendance->override_reason);
        $this->assertEquals($this->admin->id, $attendance->overridden_by);
        
        // Test relationship
        $this->assertNotNull($attendance->overriddenBy);
        $this->assertEquals($this->admin->name, $attendance->overriddenBy->name);

        // Fetch logs page and verify overrides are loaded
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.logs', ['date' => $dateStr]));
        $response->assertStatus(200);
        $response->assertViewHas('groupedOverrides');
        $groupedOverrides = $response->viewData('groupedOverrides');
        
        $found = false;
        foreach ($groupedOverrides as $group) {
            if ($group['items']->contains($attendance)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Attendance record should be present in groupedOverrides items.');
    }
}

