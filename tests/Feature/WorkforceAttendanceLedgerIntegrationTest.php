<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class WorkforceAttendanceLedgerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $cycle;
    protected $payrollRecord;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-10');

        PayrollSetting::seedDefaults();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
            'leave_balance' => 10.00,
        ]);

        $dept = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'shift_start_time' => '09:00:00',
            'shift_end_time' => '17:30:00',
            'grace_minutes' => 15,
        ]);

        $this->employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $dept->id,
            'joining_date' => '2026-06-01',
            'leave_balance' => 10.00,
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee->id,
            'designation' => 'Software Engineer',
            'employee_category' => 'Permanent',
            'shift' => 'Regular Shift',
            'location' => 'HQ, Dehradun',
        ]);

        $this->employee->payrollProfile()->create([
            'base_salary' => 60000.00,
            'salary_effective_date' => '2026-06-01',
            'payroll_enabled' => true,
        ]);

        $this->employee->leaveBalance()->create([
            'remaining_leave' => 10.00,
        ]);

        $this->cycle = PayrollCycle::create([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'period' => 'June 2026',
            'status' => 'open',
        ]);

        $this->payrollRecord = PayrollRecord::create([
            'payroll_cycle_id' => $this->cycle->id,
            'user_id' => $this->employee->id,
            'base_salary' => 60000.00,
            'gross_salary' => 60000.00,
            'net_salary' => 60000.00,
            'working_days' => 22,
            'present_days' => 22,
            'locked' => false,
            'calculation_version' => 1,
            'fingerprint' => 'initial_fingerprint',
            'employee_review_status' => 'approved',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_assign_leave_from_ledger()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.attendance.ledger.assign-leave'), [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'leave_type' => 'planned',
            'duration' => 'full_day',
            'reason' => 'Family event at home',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify leave request was created and approved
        $leave = LeaveRequest::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($leave);
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals('planned', $leave->leave_type);

        // Verify payroll record was updated (recalculated and version bumped)
        $this->payrollRecord->refresh();
        $this->assertNotEquals('initial_fingerprint', $this->payrollRecord->fingerprint);
        $this->assertEquals(2, $this->payrollRecord->calculation_version);
        $this->assertEquals('stale', $this->payrollRecord->employee_review_status);
    }

    public function test_admin_can_change_shift_from_ledger()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.attendance.ledger.change-shift'), [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'shift_start_time' => '10:00:00',
            'shift_end_time' => '18:30:00',
            'grace_minutes' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify shift metadata on the Attendance record
        $attendance = Attendance::where('user_id', $this->employee->id)->where('date', Carbon::parse('2026-06-15')->startOfDay())->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('10:00:00', $attendance->metadata['shift_start_time']);
        $this->assertEquals(5, $attendance->metadata['grace_minutes']);

        // Verify payroll record recalculated
        $this->payrollRecord->refresh();
        $this->assertEquals(2, $this->payrollRecord->calculation_version);
    }

    public function test_dossier_returns_audit_logs_and_leave_id()
    {
        $this->actingAs($this->admin);

        // Create an approved leave request
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'planned',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'total_days' => 1.0,
            'status' => 'pending',
            'reason' => 'Vacation time',
            'is_paid' => true,
        ]);

        // Approve leave
        \App\Services\LeaveBalanceService::approveRequest($leave, $this->admin, 'Approved for test');

        $response = $this->getJson(route('admin.attendance.ledger.dossier', [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('leave_context.leave_id', $leave->id)
            ->assertJsonPath('leave_context.status', 'approved');

        $data = $response->json();
        $this->assertNotEmpty($data['audit']);
        // Verify we have a leave log inside audit
        $this->assertStringContainsString('Leave request approved', $data['audit'][0]['action']);
    }

    public function test_cannot_mutate_attendance_if_payroll_locked()
    {
        // Lock the payroll record
        $this->payrollRecord->update(['locked' => true]);

        $this->actingAs($this->admin);

        // Test Override
        $responseOverride = $this->postJson(route('admin.attendance.ledger.override'), [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'status' => 'present',
            'override_reason' => 'Forgot to punch',
        ]);
        $responseOverride->assertStatus(422)
            ->assertJson(['error' => 'Cannot modify attendance. Payroll is locked and immutable for this period.']);

        // Test Assign Leave
        $responseLeave = $this->postJson(route('admin.attendance.ledger.assign-leave'), [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'leave_type' => 'planned',
            'duration' => 'full_day',
            'reason' => 'Family event',
        ]);
        $responseLeave->assertStatus(422)
            ->assertJson(['error' => 'Cannot assign leave. Payroll is locked and immutable for this period.']);

        // Test Change Shift
        $responseShift = $this->postJson(route('admin.attendance.ledger.change-shift'), [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '16:30:00',
            'grace_minutes' => 10,
        ]);
        $responseShift->assertStatus(422)
            ->assertJson(['error' => 'Cannot modify shift. Payroll is locked and immutable for this period.']);
    }
}
