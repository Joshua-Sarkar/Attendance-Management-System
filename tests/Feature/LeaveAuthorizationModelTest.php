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
        $this->assertEquals('on_leave', $employeeRecord->today_attendance->status);
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
        $credit = LeaveCredit::where('user_id', $this->employee->id)->first();
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
        
        // Before check-in: dynamic resolves as on_leave
        $recordBefore = $attendanceService->getTodayAttendance($this->employee);
        $this->assertEquals('on_leave', $recordBefore->status);

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
        // Unlock on Feb 27 (Birthday - 1 day of resolved Feb 28 birthday)
        Carbon::setTestNow('2026-02-27 12:00:00');
        $this->employee->syncBirthdayCredits();
        
        $credit = LeaveCredit::where('user_id', $this->employee->id)
            ->where('source_identifier', 'birthday_2026')
            ->first();
            
        $this->assertNotNull($credit);
        $this->assertEquals('active', $credit->status);
        // expires 12 months after Feb 28, 2026 -> Feb 28, 2027
        $this->assertEquals('2027-02-28', $credit->expires_at->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function first_of_month_birthday_unlocks_on_first_day_of_month(): void
    {
        // Birthday on July 1, 1999
        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'date_of_birth' => '1999-07-01',
        ]);

        // Test at Birthday - 1 day (June 30 - locked/not synced)
        Carbon::setTestNow('2026-06-30 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNull($credit);

        // Test at Birthday (July 1 - unlocked)
        Carbon::setTestNow('2026-07-01 12:00:00');
        $this->employee->syncBirthdayCredits();
        $credit = LeaveCredit::where('user_id', $this->employee->id)->where('source_identifier', 'birthday_2026')->first();
        $this->assertNotNull($credit);
        $this->assertEquals('active', $credit->status);
        $this->assertEquals('2026-07-01', $credit->unlocked_at->format('Y-m-d'));

        Carbon::setTestNow();
    }
}
