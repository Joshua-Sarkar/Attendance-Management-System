<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestLog;
use App\Models\LeaveCredit;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    /**
     * Submit and auto-approve Birthday Leave.
     */
    public static function submitBirthdayLeave(User $user, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate, string $reason): LeaveRequest
    {
        $today = \Carbon\Carbon::today();
        $availableToday = $user->getAvailableBirthdayYears($today);
        $availableForLeave = $user->getAvailableBirthdayYears($startDate);

        $matchingCredit = null;
        foreach ($availableToday as $cToday) {
            foreach ($availableForLeave as $cLeave) {
                if ($cToday['credit_id'] === $cLeave['credit_id']) {
                    $matchingCredit = $cToday;
                    break 2;
                }
            }
        }

        if (!$matchingCredit) {
            throw new \Exception("Birthday Leave credit is not available, locked, or has expired for these dates.");
        }
        $selectedCredit = $matchingCredit;

        return DB::transaction(function () use ($user, $startDate, $endDate, $reason, $selectedCredit) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $credit = LeaveCredit::where('id', $selectedCredit['credit_id'])->lockForUpdate()->first();

            if ($credit->status !== 'active' || $credit->used_amount >= $credit->amount) {
                throw new \Exception("Birthday Leave credit has already been consumed or is inactive.");
            }

            // Consume the credit
            $credit->used_amount = $credit->amount;
            $credit->save();

            $request = LeaveRequest::create([
                'user_id' => $lockedUser->id,
                'leave_type' => 'complimentary',
                'leave_credit_id' => $credit->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => 1,
                'reason' => $reason,
                'status' => 'approved',
                'approver_id' => null,
                'approved_at' => now(),
                'notes' => 'Automatically approved Birthday Leave.',
                'is_paid' => true,
                'metadata' => ['is_birthday' => true],
            ]);

            LeaveLedgerEntry::create([
                'user_id' => $lockedUser->id,
                'leave_request_id' => $request->id,
                'amount' => 0.00,
                'type' => 'deduction',
                'description' => 'Birthday Leave approved (Paid)',
            ]);

            LeaveRequestLog::create([
                'leave_request_id' => $request->id,
                'from_status' => null,
                'to_status' => 'pending',
                'action' => 'applied',
                'notes' => 'Applied for Birthday Leave.',
                'user_id' => $lockedUser->id,
            ]);

            LeaveRequestLog::create([
                'leave_request_id' => $request->id,
                'from_status' => 'pending',
                'to_status' => 'approved',
                'action' => 'approved',
                'notes' => 'System automatically approved Birthday Leave.',
                'user_id' => $lockedUser->id,
            ]);

            return $request;
        });
    }

    /**
     * Initialize leave balance for a new employee.
     */
    public static function initializeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->leave_balance = 2.00;
            $user->save();

            LeaveLedgerEntry::create([
                'user_id' => $user->id,
                'amount' => 2.00,
                'type' => 'opening_balance',
                'description' => 'Opening leave balance',
            ]);

            $user->payrollProfile()->create([
                'base_salary' => null,
                'salary_effective_date' => null,
                'payroll_enabled' => false,
                'import_source' => 'Manual',
            ]);

            $user->leaveBalance()->create([
                'planned_leave' => 0.00,
                'unplanned_leave' => 0.00,
                'paternity_leave' => 0.00,
                'maternity_leave' => 0.00,
                'compensatory_leave' => 0.00,
                'pending_leave' => 0.00,
                'utilized_leave' => 0.00,
                'carry_forward' => 0.00,
                'remaining_leave' => 2.00,
                'import_source' => 'Manual',
            ]);
        });
    }
}
