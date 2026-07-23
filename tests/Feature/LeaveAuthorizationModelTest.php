<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveCredit;
use App\Models\LeaveLedgerEntry;
use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveAuthorizationModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $employee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-25 09:00:00'); // Thursday

        $this->department = Department::create(['name' => 'Engineering']);

        $this->admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'System Admin',
            'email' => 'admin@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
            'leave_balance' => 10.00,
        ]);

        $this->manager = User::create([
            'employee_id' => 'MGR001',
            'name' => 'Direct Manager',
            'email' => 'manager@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->department->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Standard Employee',
            'email' => 'employee@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $this->department->id,
            'manager_id' => $this->manager->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function planned_leave_approval_deducts_balance_and_attendance_resolves_to_on_leave(): void
    {
        $targetDate = Carbon::today()->addDays(5)->format('Y-m-d');

        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'R&R',
            'status' => 'pending',
        ]);

        $this->actingAs($this->manager)->post(route('leaves.approve', $leave), [
            'notes' => 'Have fun!',
        ]);

        $this->employee->refresh();
        $this->assertEquals(9.00, $this->employee->leave_balance);

        $attendanceService = resolve(\App\Services\AttendanceService::class);
        $employees = $attendanceService->getFilteredAttendance($targetDate, null, null, $this->manager);
        $employeeRecord = $employees->firstWhere('id', $this->employee->id);
        
        $this->assertNotNull($employeeRecord->today_attendance);
        $this->assertEquals('planned', $employeeRecord->today_attendance->status);
    }

    /** @test */
    public function planned_leave_rejection_does_not_deduct_balance_and_attendance_resolves_to_absent(): void
    {
        $targetDate = Carbon::today()->addDays(5)->format('Y-m-d');

        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'R&R',
            'status' => 'pending',
        ]);

        $this->actingAs($this->manager)->post(route('leaves.reject', $leave), [
            'rejection_reason' => 'Too busy right now.',
        ]);

        $this->employee->refresh();
        $this->assertEquals(10.00, $this->employee->leave_balance);

        $attendanceService = resolve(\App\Services\AttendanceService::class);
        // Dynamic stats checks: on a day with no check-in and no approved leave, it counts as absent
        $stats = $attendanceService->getTodayStats($targetDate, null, $this->manager);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(0, $stats['on_leave']);
    }

    /** @test */
    public function birthday_credit_is_dynamically_synced_unlocked_and_expired(): void
    {
        // 1. Employee born on Oct 10, 1995
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1995-10-10',
        ]);

        // Test at Birthday - 2 days (locked/not synced yet)
        Carbon::setTestNow('2026-10-08 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNull($credit);

        // Test at Birthday - 1 day (unlocked/synced)
        Carbon::setTestNow('2026-10-09 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNotNull($credit);
        $this->assertEquals('active', $credit->status);
        $this->assertEquals(0.00, $credit->used_amount);

        // Test at Birthday + 12 months + 1 day (expired)
        Carbon::setTestNow('2027-10-11 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit->refresh();
        $this->assertEquals('expired', $credit->status);

        Carbon::setTestNow();
    }

    /** @test */
    public function birthday_leave_request_auto_approved_and_consumes_credit(): void
    {
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1995-10-10',
        ]);

        Carbon::setTestNow('2026-10-10 12:00:00');

        // Apply for Birthday Leave (complimentary)
        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'complimentary',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'reason' => 'Celebrating my birthday.',
        ]);

        $response->assertRedirect(route('leaves.index'));

        // Refresh and check credit is consumed
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNotNull($credit);
        $this->assertEquals(1.00, $credit->used_amount);

        // Check leave request is approved
        $leave = LeaveRequest::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($leave);
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals('complimentary', $leave->leave_type);

        // Regular leave balance untouched
        $this->employee->refresh();
        $this->assertEquals(10.00, $this->employee->leave_balance);

        Carbon::setTestNow();
    }

    /** @test */
    public function birthday_leave_rejection_via_override_restores_credit_and_updates_attendance(): void
    {
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1995-10-10',
        ]);

        Carbon::setTestNow('2026-10-10 12:00:00');

        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->first();
        $credit->update(['used_amount' => 1.00]);

        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'complimentary',
            'leave_credit_id' => $credit->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'total_days' => 1,
            'reason' => 'Birthday',
            'status' => 'approved',
        ]);

        // Override to rejected by Admin
        $response = $this->actingAs($this->admin)->post(route('leaves.override', $leave), [
            'override_status' => 'rejected',
            'override_notes' => 'Cancelling birthday leave due to schedule conflict.',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $credit->refresh();
        $this->assertEquals(0.00, $credit->used_amount);

        $leave->refresh();
        $this->assertEquals('rejected', $leave->status);

        // Attendance stats should count as absent on this day now
        $attendanceService = resolve(\App\Services\AttendanceService::class);
        $stats = $attendanceService->getTodayStats('2026-10-10', null, $this->manager);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(0, $stats['on_leave']);

        Carbon::setTestNow();
    }

    /** @test */
    public function physical_check_in_overrides_approved_leave_request(): void
    {
        $targetDate = Carbon::today();
        $targetDateStr = $targetDate->format('Y-m-d');

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Planned holiday',
            'status' => 'approved',
        ]);

        $attendanceService = resolve(\App\Services\AttendanceService::class);
        
        // Before check-in: dynamic resolves as planned
        $recordBefore = $attendanceService->getTodayAttendance($this->employee);
        $this->assertEquals('planned', $recordBefore->status);

        // Clock in physically
        $attendanceService->checkIn($this->employee);

        // After check-in: resolves to present or late overriding on_leave
        $recordAfter = $attendanceService->getTodayAttendance($this->employee);
        $this->assertContains($recordAfter->status, ['present', 'late']);
    }

    /** @test */
    public function leap_year_birthday_reserves_correctly_on_non_leap_years(): void
    {
        // February 29, 1996 birthday
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1996-02-29',
        ]);

        // Year 2026 (non-leap year)
        // Unlock on Feb 26 (Birthday - 1 day of resolved Feb 27 birthday)
        Carbon::setTestNow('2026-02-26 12:00:00');
        $this->employee->syncBirthdayCredits();
        
        $credit = LeaveCredit::where('user_id', $this->employee->id)
            ->where('source_identifier', 'birthday_2026')
            ->first();
            
        $this->assertNotNull($credit);
        $this->assertEquals('active', $credit->status);
        // expires 1 year after Feb 26, 2026 -> Feb 26, 2027
        $this->assertEquals('2027-02-26', $credit->expires_at->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function first_of_month_birthday_unlocks_exactly_one_day_before(): void
    {
        // Birthday on July 1, 1999
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1999-07-01',
        ]);

        // Test at Birthday - 2 days (June 29 - locked/not synced)
        Carbon::setTestNow('2026-06-29 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNull($credit);

        // Test at Birthday - 1 day (June 30 - unlocked)
        Carbon::setTestNow('2026-06-30 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNotNull($credit);
        $this->assertEquals('active', $credit->status);
        $this->assertEquals('2026-06-30', $credit->unlocked_at->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function birthday_leave_cannot_be_submitted_before_becoming_eligible(): void
    {
        // Birthday on July 1, 1999
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1999-07-01',
            'joining_date' => '2026-06-01',
        ]);

        // Submit date is June 28 (Birthday - 3 days - ineligible)
        Carbon::setTestNow('2026-06-28 12:00:00');

        $response = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'complimentary',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'reason' => 'My Birthday Celebration',
        ]);

        $response->assertSessionHasErrors(['leave_type']);

        // Check no request was created
        $this->assertEquals(0, LeaveRequest::where('user_id', $this->employee->id)->count());

        Carbon::setTestNow();
    }

    /** @test */
    public function it_generates_correct_leave_type_display_labels(): void
    {
        // 1. Birthday Leave (Paid)
        $birthdayLeave = new LeaveRequest(['leave_type' => 'complimentary', 'is_paid' => true]);
        $this->assertEquals('Birthday Leave (Paid)', $birthdayLeave->leave_type_label);

        $birthdayLeave2 = new LeaveRequest(['leave_credit_id' => 123, 'is_paid' => true]);
        $this->assertEquals('Birthday Leave (Paid)', $birthdayLeave2->leave_type_label);

        $birthdayLeave3 = new LeaveRequest(['leave_type' => 'birthday_leave', 'is_paid' => true]);
        $this->assertEquals('Birthday Leave (Paid)', $birthdayLeave3->leave_type_label);

        $birthdayLeave4 = new LeaveRequest(['leave_type' => 'planned', 'is_paid' => true, 'metadata' => ['is_birthday' => true]]);
        $this->assertEquals('Birthday Leave (Paid)', $birthdayLeave4->leave_type_label);

        // 2. Planned Leave (Paid)
        $plannedPaid = new LeaveRequest(['leave_type' => 'planned', 'is_paid' => true]);
        $this->assertEquals('Planned Leave (Paid)', $plannedPaid->leave_type_label);

        // 3. Planned Leave (Unpaid)
        $plannedUnpaid = new LeaveRequest(['leave_type' => 'planned', 'is_paid' => false]);
        $this->assertEquals('Planned Leave (Unpaid)', $plannedUnpaid->leave_type_label);

        $unpaidLeave = new LeaveRequest(['leave_type' => 'unpaid', 'is_paid' => false]);
        $this->assertEquals('Planned Leave (Unpaid)', $unpaidLeave->leave_type_label);

        // 4. Unplanned Leave (Paid)
        $unplannedPaid = new LeaveRequest(['leave_type' => 'unplanned', 'is_paid' => true]);
        $this->assertEquals('Unplanned Leave (Paid)', $unplannedPaid->leave_type_label);

        // 5. Unplanned Leave (Unpaid)
        $unplannedUnpaid = new LeaveRequest(['leave_type' => 'unplanned', 'is_paid' => false]);
        $this->assertEquals('Unplanned Leave (Unpaid)', $unplannedUnpaid->leave_type_label);

        // 6. Sick Leave
        $sickLeave = new LeaveRequest(['leave_type' => 'sick_leave', 'is_paid' => true]);
        $this->assertEquals('Sick Leave', $sickLeave->leave_type_label);

        $sickLeave2 = new LeaveRequest(['leave_type' => 'sick', 'is_paid' => true]);
        $this->assertEquals('Sick Leave', $sickLeave2->leave_type_label);

        // 7. Emergency Leave
        $emergencyLeave = new LeaveRequest(['leave_type' => 'emergency_leave', 'is_paid' => true]);
        $this->assertEquals('Emergency Leave', $emergencyLeave->leave_type_label);

        $emergencyLeave2 = new LeaveRequest(['leave_type' => 'emergency', 'is_paid' => true]);
        $this->assertEquals('Emergency Leave', $emergencyLeave2->leave_type_label);

        // 8. Legacy Leave Records
        $casual = new LeaveRequest(['leave_type' => 'casual_leave', 'is_paid' => true]);
        $this->assertEquals('Planned Leave (Paid)', $casual->leave_type_label);

        $paid = new LeaveRequest(['leave_type' => 'paid_leave', 'is_paid' => true]);
        $this->assertEquals('Planned Leave (Paid)', $paid->leave_type_label);

        $unpaidLeaveLegacy = new LeaveRequest(['leave_type' => 'unpaid_leave', 'is_paid' => false]);
        $this->assertEquals('Planned Leave (Unpaid)', $unpaidLeaveLegacy->leave_type_label);

        // 9. Unknown leave values (fallback behavior)
        $unknown1 = new LeaveRequest(['leave_type' => 'special_event', 'is_paid' => true]);
        $this->assertEquals('Special Event', $unknown1->leave_type_label);

        $unknown2 = new LeaveRequest(['leave_type' => 'bereavement_leave', 'is_paid' => false]);
        $this->assertEquals('Bereavement Leave', $unknown2->leave_type_label);
    }

    /** @test */
    public function birthday_leave_time_gating_eligibility(): void
    {
        // Birthday: November 6, 1990
        // Joining: June 1, 2026
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1990-11-06',
            'joining_date' => '2026-06-01',
        ]);

        // 1. Two days before birthday (November 4, 2026) -> NOT eligible
        Carbon::setTestNow('2026-11-04 12:00:00');
        $this->employee->syncBirthdayCredits();
        $this->assertFalse(User::canUseBirthdayLeave($this->employee, Carbon::today()));

        // Dropdown visibility on November 4
        $response = $this->actingAs($this->employee)->get(route('leaves.create'));
        $response->assertStatus(200);
        $response->assertDontSee('Birthday Leave');

        // Backend submission rejection on November 4
        $responseStore = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'complimentary',
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-06',
            'reason' => 'Birthday Leave Celebration Request',
        ]);
        $responseStore->assertSessionHasErrors(['leave_type']);

        // 2. One day before birthday (November 5, 2026) -> ELIGIBLE
        Carbon::setTestNow('2026-11-05 12:00:00');
        $this->employee->syncBirthdayCredits();
        $this->assertTrue(User::canUseBirthdayLeave($this->employee, Carbon::today()));

        // Dropdown visibility on November 5
        $response2 = $this->actingAs($this->employee)->get(route('leaves.create'));
        $response2->assertStatus(200);
        $response2->assertSee('Birthday Leave');

        // Backend submission approval on November 5
        $responseStore2 = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'complimentary',
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-06',
            'reason' => 'Birthday Leave Celebration Request',
        ]);
        $responseStore2->assertRedirect();
        $responseStore2->assertSessionHasNoErrors();

        Carbon::setTestNow();
    }
}
