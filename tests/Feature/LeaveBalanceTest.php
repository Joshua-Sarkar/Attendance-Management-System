<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveLedgerEntry;
use App\Services\EmployeeService;
use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class LeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test manual creation initialization.
     */
    public function test_manual_employee_creation_initializes_leave_balance(): void
    {
        // Setup service
        $service = resolve(EmployeeService::class);

        $admin = User::factory()->create(['role' => 'admin']);

        // Data for employee creation
        $data = [
            'employee_id' => 'EMP99999',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
            'joining_date' => '2026-06-23',
            'must_change_password' => true,
            'department_id' => null,
            'manager_id' => null,
            'admin_id' => $admin->id,
        ];

        // Create employee
        $employee = $service->create($data);

        // Verify balance
        $this->assertEquals(2.00, $employee->leave_balance);

        // Verify ledger entry
        $ledgerEntry = LeaveLedgerEntry::where('user_id', $employee->id)->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(2.00, $ledgerEntry->amount);
        $this->assertEquals('opening_balance', $ledgerEntry->type);
        $this->assertEquals('Opening leave balance', $ledgerEntry->description);
    }

    /**
     * Test backfill command leaves:initialize-balances.
     */
    public function test_initialize_balances_command_backfills_and_is_idempotent(): void
    {
        // Disable the onboarding trigger to simulate pre-existing users without balances
        // We do this by creating users directly via DB or factory, bypassing EmployeeService
        $user1 = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'leave_balance' => 0.00,
        ]);

        $user2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'leave_balance' => 0.00,
        ]);

        // Run backfill command
        Artisan::call('leaves:initialize-balances');

        // Refresh models
        $user1->refresh();
        $user2->refresh();

        // user1 (employee) should be initialized
        $this->assertEquals(2.00, $user1->leave_balance);
        $this->assertTrue(LeaveLedgerEntry::where('user_id', $user1->id)->where('type', 'opening_balance')->exists());

        // user2 (admin) should NOT be initialized
        $this->assertEquals(0.00, $user2->leave_balance);
        $this->assertFalse(LeaveLedgerEntry::where('user_id', $user2->id)->where('type', 'opening_balance')->exists());

        // Run the command again to test idempotency
        Artisan::call('leaves:initialize-balances');

        // Confirm it didn't double-charge
        $user1->refresh();
        $this->assertEquals(2.00, $user1->leave_balance);
        $this->assertEquals(1, LeaveLedgerEntry::where('user_id', $user1->id)->where('type', 'opening_balance')->count());
    }

    /**
     * Test monthly accrual command leaves:accrue.
     */
    public function test_monthly_accrual_command_adds_credits_and_logs(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 00:00:00'));

        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'leave_balance' => 2.00,
        ]);

        // Run accrue command
        Artisan::call('leaves:accrue');

        $employee->refresh();

        // Verify balance
        $this->assertEquals(4.00, $employee->leave_balance);

        // Verify ledger entry
        $accrualLedger = LeaveLedgerEntry::where('user_id', $employee->id)
            ->where('type', 'accrual')
            ->first();
        $this->assertNotNull($accrualLedger);
        $this->assertEquals(2.00, $accrualLedger->amount);
        $this->assertEquals('Monthly accrual for July 2026', $accrualLedger->description);

        Carbon::setTestNow();
    }

    /**
     * Test Admin auto-approval balance verification and deduction.
     */
    public function test_admin_leave_submission_balance_validation_and_deduction(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'leave_balance' => 2.00,
        ]);

        $this->actingAs($admin);

        // 1. Try to request 3 days (insufficient)
        $response = $this->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->addDays(2)->format('Y-m-d'), // 3 days
            'reason' => 'Testing validation',
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $admin->refresh();
        $this->assertEquals(2.00, $admin->leave_balance);

        // 2. Request 1 day (sufficient)
        $response = $this->post(route('leaves.store'), [
            'leave_type' => 'planned',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'), // 1 day
            'reason' => 'Testing validation',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $admin->refresh();
        $this->assertEquals(1.00, $admin->leave_balance);

        $leaveRequest = LeaveRequest::where('user_id', $admin->id)->first();
        $this->assertEquals('approved', $leaveRequest->status);

        // Verify ledger deduction entry
        $this->assertTrue(LeaveLedgerEntry::where('user_id', $admin->id)
            ->where('type', 'deduction')
            ->where('amount', -1.00)
            ->exists());
    }

    /**
     * Test Manager/Admin approval and rejection validation.
     */
    public function test_leave_approval_validates_and_deducts_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'leave_balance' => 1.00,
        ]);

        // Create pending 2-day leave request
        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'start_date' => today(),
            'end_date' => today()->addDay(), // 2 days
            'total_days' => 2,
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        // 1. Attempt to approve (should fail due to insufficient balance)
        $response = $this->post(route('leaves.approve', $leaveRequest), [
            'notes' => 'Some notes',
        ]);
        $response->assertSessionHas('error');
        $employee->refresh();
        $this->assertEquals(1.00, $employee->leave_balance);

        // 2. Give employee enough balance and approve
        $employee->leave_balance = 3.00;
        $employee->save();

        $response = $this->post(route('leaves.approve', $leaveRequest), [
            'notes' => 'Approved',
        ]);
        $response->assertRedirect(route('leaves.index'));
        
        $employee->refresh();
        $leaveRequest->refresh();

        $this->assertEquals('approved', $leaveRequest->status);
        $this->assertEquals(1.00, $employee->leave_balance);

        // Verify ledger deduction
        $this->assertTrue(LeaveLedgerEntry::where('user_id', $employee->id)
            ->where('type', 'deduction')
            ->where('amount', -2.00)
            ->exists());
    }

    /**
     * Test cancellation refunds.
     */
    public function test_cancellation_refunds_approved_leave_balance(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'leave_balance' => 1.00,
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'start_date' => today(),
            'end_date' => today()->addDay(), // 2 days
            'total_days' => 2,
            'reason' => 'Vacation',
            'status' => 'approved',
        ]);

        $this->actingAs($employee);

        // Cancel approved request
        $response = $this->post(route('leaves.cancel', $leaveRequest));
        $response->assertRedirect(route('leaves.index'));

        $employee->refresh();
        $leaveRequest->refresh();

        $this->assertEquals('cancelled', $leaveRequest->status);
        $this->assertEquals(3.00, $employee->leave_balance); // 1.00 + 2.00 refund

        // Verify ledger refund
        $this->assertTrue(LeaveLedgerEntry::where('user_id', $employee->id)
            ->where('type', 'refund')
            ->where('amount', 2.00)
            ->exists());
    }

    /**
     * Test Admin override state transitions.
     */
    public function test_admin_override_deducts_and_refunds_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'leave_balance' => 3.00,
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'planned',
            'start_date' => today(),
            'end_date' => today()->addDay(), // 2 days
            'total_days' => 2,
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        // 1. Override from pending to approved (should deduct 2 days)
        $response = $this->post(route('leaves.override', $leaveRequest), [
            'override_status' => 'approved',
            'override_notes' => 'Approved via override',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $employee->refresh();
        $leaveRequest->refresh();

        $this->assertEquals('approved', $leaveRequest->status);
        $this->assertEquals(1.00, $employee->leave_balance);

        // 2. Override from approved to rejected (should refund 2 days)
        $response = $this->post(route('leaves.override', $leaveRequest), [
            'override_status' => 'rejected',
            'override_notes' => 'Rejected via override',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $employee->refresh();
        $leaveRequest->refresh();

        $this->assertEquals('rejected', $leaveRequest->status);
        $this->assertEquals(3.00, $employee->leave_balance);
    }
}
