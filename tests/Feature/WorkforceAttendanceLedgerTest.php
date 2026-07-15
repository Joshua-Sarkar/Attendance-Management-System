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
use App\Services\AttendanceService;
use App\Services\PayrollService;
use App\Events\AttendanceOverridden;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class WorkforceAttendanceLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $manager;
    protected $employee1;
    protected $employee2;
    protected $cycle;
    protected $payrollRecord1;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::seedDefaults();

        // 1. Create Admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'joining_date' => '2025-01-10',
        ]);

        // 2. Create Manager
        $this->manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'joining_date' => '2025-02-15',
        ]);

        // 3. Create Department
        $dept = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        // 4. Create standard employee reporting to Manager
        $this->employee1 = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $dept->id,
            'manager_id' => $this->manager->id,
            'joining_date' => '2026-06-10',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee1->id,
            'designation' => 'Software Engineer',
            'employee_category' => 'Permanent',
            'shift' => 'Regular Shift',
            'location' => 'HQ, Dehradun',
        ]);

        $this->employee1->payrollProfile()->create([
            'base_salary' => 60000.00,
            'salary_effective_date' => '2026-06-10',
            'payroll_enabled' => true,
        ]);

        // 5. Create another standard employee reporting to Admin (not manager)
        $this->employee2 = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'department_id' => $dept->id,
            'manager_id' => $this->admin->id,
            'joining_date' => '2026-06-10',
        ]);

        EmployeeProfile::create([
            'user_id' => $this->employee2->id,
            'designation' => 'Designer',
            'employee_category' => 'Permanent',
            'shift' => 'Regular Shift',
            'location' => 'HQ, Dehradun',
        ]);

        $this->employee2->payrollProfile()->create([
            'base_salary' => 50000.00,
            'salary_effective_date' => '2026-06-10',
            'payroll_enabled' => true,
        ]);

        // Seed some attendance record for employee 1
        Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-07-03',
            'status' => 'present',
            'classification' => 'full_day',
            'check_in_time' => '2026-07-03 09:15:00',
            'check_out_time' => '2026-07-03 18:15:00',
            'hours' => 9.0,
        ]);

        // Process a cycle to create baseline payroll record
        $this->cycle = PayrollService::processCycle('2026-07-01', $this->admin);
        $this->cycle->update(['status' => 'active']);

        $this->payrollRecord1 = PayrollRecord::where('user_id', $this->employee1->id)
            ->where('payroll_cycle_id', $this->cycle->id)
            ->first();
    }

    /** @test */
    public function standard_employee_cannot_access_ledger()
    {
        $response = $this->actingAs($this->employee1)
            ->get(route('admin.attendance.ledger'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_ledger_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.ledger', ['month' => '2026-07']));

        $response->assertStatus(200);
        $response->assertViewHas('matrix');
        $response->assertViewHas('metrics');
    }

    /** @test */
    public function manager_can_access_ledger_but_only_sees_reporting_employees()
    {
        $response = $this->actingAs($this->manager)
            ->get(route('admin.attendance.ledger', ['month' => '2026-07']));

        $response->assertStatus(200);
        
        $matrix = $response->viewData('matrix');
        
        // Manager's reporting employee should be in the matrix
        $this->assertArrayHasKey($this->employee1->id, $matrix);
        // Non-reporting employee should NOT be in the matrix
        $this->assertArrayNotHasKey($this->employee2->id, $matrix);
    }

    /** @test */
    public function detailed_day_dossier_returns_correct_metadata_and_payroll_impact()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.attendance.ledger.dossier', [
                'employee_id' => $this->employee1->id,
                'date' => '2026-07-03',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'employee' => ['id', 'name', 'department', 'designation'],
            'date_context' => ['date', 'day_of_week', 'is_working_day'],
            'resolved' => ['status', 'hours', 'classification'],
            'payroll_impact' => ['period', 'locked', 'daily_rate', 'deduction_factor'],
        ]);
        
        $data = $response->json();
        $this->assertEquals('present', $data['resolved']['status']);
        $this->assertEquals(9.0, $data['resolved']['hours']);
        $this->assertEquals('2026-07-01', $data['payroll_impact']['period']);
    }

    /** @test */
    public function individual_override_preserves_automatic_state_and_dispatches_event()
    {
        Event::fake();

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.attendance.ledger.override'), [
                'employee_id' => $this->employee1->id,
                'date' => '2026-07-03',
                'status' => 'absent',
                'classification' => 'full_day',
                'override_reason' => 'Manually marked absent for training omission',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify the record was updated and automatic state preserved separately
        $record = Attendance::where('user_id', $this->employee1->id)->first();

        $this->assertNotNull($record);
        $this->assertEquals('absent', $record->status);
        $this->assertEquals('present', $record->automatic_status);
        $this->assertTrue($record->is_overridden);
        $this->assertEquals($this->admin->id, $record->overridden_by);
        $this->assertEquals('Manually marked absent for training omission', $record->override_reason);

        // Verify event was dispatched
        Event::assertDispatched(AttendanceOverridden::class, function ($event) {
            return $event->employee->id === $this->employee1->id &&
                   $event->date->format('Y-m-d') === '2026-07-03' &&
                   $event->actor->id === $this->admin->id;
        });
    }

    /** @test */
    public function individual_override_recalculates_payroll_and_invalidates_approvals()
    {
        // Set initial approvals on the payroll record
        $this->payrollRecord1->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $this->admin->id,
        ]);

        $initialFingerprint = $this->payrollRecord1->fingerprint;
        $initialVersion = $this->payrollRecord1->calculation_version ?: 1;

        // Perform override to 'absent', which should trigger deduction and change fingerprint
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.attendance.ledger.override'), [
                'employee_id' => $this->employee1->id,
                'date' => '2026-07-03',
                'status' => 'absent',
                'classification' => 'full_day',
                'override_reason' => 'Manual override to absent',
            ]);

        $response->assertStatus(200);

        // Fetch refreshed payroll record
        $refreshed = $this->payrollRecord1->refresh();

        $this->assertNotEquals($initialFingerprint, $refreshed->fingerprint);
        $this->assertEquals($initialVersion + 1, $refreshed->calculation_version);
        $this->assertEquals('stale', $refreshed->employee_review_status);
        $this->assertNull($refreshed->employee_approved_at);
        $this->assertNull($refreshed->admin_approved_at);
        $this->assertNull($refreshed->admin_approved_by_id);
    }

    /** @test */
    public function locked_payroll_remains_immutable_and_rejects_override()
    {
        // Lock the payroll record
        $this->payrollRecord1->update([
            'locked' => true,
            'locked_at' => now(),
            'locked_by_id' => $this->admin->id,
        ]);

        // Attempt override on a date in locked cycle
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.attendance.ledger.override'), [
                'employee_id' => $this->employee1->id,
                'date' => '2026-07-03',
                'status' => 'absent',
                'classification' => 'full_day',
                'override_reason' => 'Attempt override on locked cycle',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Cannot modify attendance. Payroll is locked and immutable for this period.']);

        // Verify database remains unchanged
        $record = Attendance::where('user_id', $this->employee1->id)->first();

        $this->assertEquals('present', $record->status);
        $this->assertFalse($record->is_overridden);
    }

    /** @test */
    public function exact_late_minutes_are_calculated_correctly_from_shift_start()
    {
        // Late arrival test
        $att = Attendance::create([
            'user_id' => $this->employee1->id,
            'date' => '2026-07-04',
            'status' => 'late',
            'classification' => 'full_day',
            'check_in_time' => '2026-07-04 09:50:00', // shift_start in seed is usually 09:30 AM
            'check_out_time' => '2026-07-04 18:30:00',
            'hours' => 8.0,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.attendance.ledger.dossier', [
                'employee_id' => $this->employee1->id,
                'date' => '2026-07-04',
            ]));

        $response->assertStatus(200);
        $data = $response->json();
        
        // shift start is 09:30 AM. check_in is 09:50 AM. difference is 20 minutes.
        $this->assertEquals(20, $data['resolved']['late_minutes']);
    }

    /** @test */
    public function ledger_index_can_be_filtered_by_department_and_date_range()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.ledger', [
                'range' => 'custom',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'department_id' => $this->employee1->department_id,
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('dateList');
        $this->assertCount(5, $response->viewData('dateList'));
    }

    /** @test */
    public function bulk_override_succeeds_for_multiple_employees_and_dates()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.attendance.ledger.bulk-override'), [
                'employee_ids' => [$this->employee1->id, $this->employee2->id],
                'date_mode' => 'range',
                'start_date' => '2026-07-02',
                'end_date' => '2026-07-04',
                'status' => 'wfh',
                'classification' => 'full_day',
                'override_reason' => 'Bulk override to work from home',
                'conflict_handling' => 'replace',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check employee 1
        $att1 = Attendance::where('user_id', $this->employee1->id)->whereDate('date', '2026-07-02')->first();
        $this->assertNotNull($att1);
        $this->assertEquals('wfh', $att1->status);
        $this->assertTrue($att1->is_overridden);

        // Check employee 2
        $att2 = Attendance::where('user_id', $this->employee2->id)->whereDate('date', '2026-07-04')->first();
        $this->assertNotNull($att2);
        $this->assertEquals('wfh', $att2->status);
        $this->assertTrue($att2->is_overridden);
    }

    /** @test */
    public function rendered_javascript_has_valid_syntax()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.ledger', ['month' => '2026-07']));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Extract script blocks
        preg_match_all('/<script>(.*?)<\/script>/is', $html, $matches);
        $this->assertNotEmpty($matches[1], 'No script tags found in page');

        foreach ($matches[1] as $idx => $js) {
            // Write to a temporary file
            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('js_test_', true) . '.js';
            file_put_contents($tempFile, $js);

            // Run node -c on it to check syntax
            exec("node -c " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);
            @unlink($tempFile);

            $this->assertEquals(0, $returnCode, "JavaScript Syntax Error in script block " . ($idx + 1) . ": " . implode("\n", $output));
        }
    }
}

