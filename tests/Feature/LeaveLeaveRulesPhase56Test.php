<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveCredit;
use App\Models\LeaveLedgerEntry;
use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeProfile;
use App\Services\AttendanceTimingResolver;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveLeaveRulesPhase56Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $employee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-30 09:00:00'); // Tuesday

        $this->department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);

        $this->admin = User::create([
            'employee_id' => 'ADM999',
            'name' => 'Admin User',
            'email' => 'admin999@ams.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'department_id' => $this->department->id,
            'leave_balance' => 10.00,
        ]);

        $this->manager = User::create([
            'employee_id' => 'MGR999',
            'name' => 'Manager User',
            'email' => 'manager999@ams.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
            'department_id' => $this->department->id,
            'admin_id' => $this->admin->id,
            'leave_balance' => 10.00,
        ]);

        $this->employee = User::create([
            'employee_id' => 'EMP999',
            'name' => 'Regular Employee',
            'email' => 'employee999@ams.com',
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
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function late_policy_resolves_classification_dynamically_from_config(): void
    {
        // 1. Setup a late check-in (grace threshold is 09:45:00 for default Engineering 09:30:00 start)
        Carbon::setTestNow('2026-06-30 10:00:00');

        // Test with config value = 'half_day' (default)
        config(['attendance.late_arrival_classification' => 'half_day']);

        $service = resolve(AttendanceService::class);
        $attendance = $service->checkIn($this->employee);

        $this->assertEquals('half', $attendance->status);
        $this->assertEquals('half_day', $attendance->classification);
        $this->assertEquals('late_arrival', $attendance->automatic_classification_reason);

        // Delete attendance to test with config value = 'full_day'
        $attendance->delete();

        config(['attendance.late_arrival_classification' => 'full_day']);
        $attendance = $service->checkIn($this->employee);

        $this->assertEquals('half', $attendance->status);
        $this->assertEquals('half_day', $attendance->classification);
    }

    /** @test */
    public function healthcare_timings_resolve_case_insensitively_for_names_and_code(): void
    {
        // Setup Healthcare department matching by code 'hlt'
        $hltDept = Department::create(['name' => 'Support Services', 'code' => 'hlt']);
        $hltEmployee = User::create([
            'employee_id' => 'HLT001',
            'name' => 'HLT Doctor',
            'email' => 'doc@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $hltDept->id,
            'leave_balance' => 10.00,
        ]);

        $timings = AttendanceTimingResolver::resolveTimings($hltEmployee, Carbon::today());
        // Healthcare defaults: Start: 10:00 AM, End: 6:00 PM, Grace: 5 minutes
        $this->assertEquals('10:00:00', $timings['start_time']);
        $this->assertEquals('18:00:00', $timings['end_time']);
        $this->assertEquals('10:05:00', $timings['grace_threshold']->format('H:i:s'));

        // Setup Healthcare department matching by name 'HEALTHCARE' (case-insensitive)
        $healthcareDept = Department::create(['name' => 'HEALTHCARE', 'code' => 'HC']);
        $hcEmployee = User::create([
            'employee_id' => 'HC001',
            'name' => 'HC Nurse',
            'email' => 'nurse@ams.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $healthcareDept->id,
            'leave_balance' => 10.00,
        ]);

        $timingsHC = AttendanceTimingResolver::resolveTimings($hcEmployee, Carbon::today());
        $this->assertEquals('10:00:00', $timingsHC['start_time']);
    }

    /** @test */
    public function unpaid_leave_type_submission_is_rejected_by_validation(): void
    {
        // Submit Unpaid Leave as Admin (should fail validation)
        $targetDate = Carbon::today()->addDays(2)->format('Y-m-d');
        
        $response = $this->actingAs($this->admin)->post(route('leaves.store'), [
            'leave_type' => 'unpaid',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'reason' => 'Unpaid leave test',
        ]);

        $response->assertSessionHasErrors(['leave_type']);
    }

    /** @test */
    public function unpaid_leave_cancellation_and_override_creates_zero_ledger_entries(): void
    {
        $targetDate = Carbon::today()->addDays(1)->format('Y-m-d');

        // Create approved Unpaid Leave
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'unpaid',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'total_days' => 1,
            'reason' => 'Unpaid leave',
            'status' => 'approved',
            'is_paid' => false,
        ]);

        // Cancel the leave
        $this->actingAs($this->employee)->post(route('leaves.cancel', $leave));

        $leave->refresh();
        $this->assertEquals('cancelled', $leave->status);

        // Cancel ledger should be 0.00
        $refundLedger = LeaveLedgerEntry::where('leave_request_id', $leave->id)->where('type', 'refund')->first();
        $this->assertNotNull($refundLedger);
        $this->assertEquals(0.00, $refundLedger->amount);

        // Reset to pending to test Admin Override
        $leave->update(['status' => 'pending']);

        // Override to approved
        $this->actingAs($this->admin)->post(route('leaves.override', $leave), [
            'override_status' => 'approved',
            'override_notes' => 'Overriding back to approved status.',
        ]);

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertFalse($leave->is_paid);

        $overrideLedger = LeaveLedgerEntry::where('leave_request_id', $leave->id)->where('type', 'deduction')->orderBy('id', 'desc')->first();
        $this->assertNotNull($overrideLedger);
        $this->assertEquals(0.00, $overrideLedger->amount);
    }

    /** @test */
    public function half_day_status_in_override_resolves_to_present_status_and_half_day_classification(): void
    {
        $targetDate = Carbon::today()->format('Y-m-d');

        // Post override with status = 'half_day' (no classification parameter submitted)
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override.store'), [
            'date' => $targetDate,
            'status' => 'half_day',
            'override_reason' => 'Admin overriding to half day',
            'user_ids' => [$this->employee->id],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $attendance = Attendance::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('half', $attendance->status);
        $this->assertEquals('half_day', $attendance->classification);
        $this->assertTrue($attendance->is_overridden);
    }

    /** @test */
    public function unplanned_leave_creation_and_approval_does_not_deduct_regular_balance(): void
    {
        // 1. Submit Unplanned Leave as Employee (Starts as Pending)
        $empDate = Carbon::today()->subDays(5)->format('Y-m-d');
        $responseEmp = $this->actingAs($this->employee)->post(route('leaves.store'), [
            'leave_type' => 'unplanned',
            'start_date' => $empDate,
            'end_date' => $empDate,
            'reason' => 'Unplanned emergency test',
        ]);

        $responseEmp->assertRedirect();
        $requestEmp = LeaveRequest::where('user_id', $this->employee->id)->where('status', 'pending')->first();
        $this->assertNotNull($requestEmp);
        $this->assertFalse($requestEmp->is_paid);

        // Approve it as manager
        $this->actingAs($this->manager)->post(route('leaves.approve', $requestEmp), [
            'notes' => 'Okay approved.',
        ]);

        $this->employee->refresh();
        $this->assertEquals(10.00, $this->employee->leave_balance); // Balance not deducted

        $requestEmp->refresh();
        $this->assertEquals('approved', $requestEmp->status);

        $ledgerEmp = LeaveLedgerEntry::where('leave_request_id', $requestEmp->id)->first();
        $this->assertNotNull($ledgerEmp);
        $this->assertEquals(0.00, $ledgerEmp->amount);
    }
}
