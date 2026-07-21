<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestLog;
use App\Models\LeaveCredit;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    /**
     * Check if payroll is locked for the dates of the leave request.
     */
    public static function checkPayrollLock(User $employee, $startDate, $endDate = null): void
    {
        $startStr = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
        $endStr = $endDate ? \Carbon\Carbon::parse($endDate)->format('Y-m-d') : $startStr;

        $locked = \App\Models\PayrollRecord::where('user_id', $employee->id)
            ->where('locked', true)
            ->whereHas('payrollCycle', function ($q) use ($startStr, $endStr) {
                $q->where(function($query) use ($startStr, $endStr) {
                    $query->whereBetween('start_date', [$startStr, $endStr])
                          ->orWhereBetween('end_date', [$startStr, $endStr])
                          ->orWhere(function($q2) use ($startStr, $endStr) {
                              $q2->where('start_date', '<=', $startStr)
                                 ->where('end_date', '>=', $endStr);
                          });
                });
            })
            ->exists();

        if ($locked) {
            throw new \Exception("Cannot modify leave request. Payroll is locked and immutable for this period.");
        }
    }

    /**
     * Submit and auto-approve Birthday Leave.
     */
    public static function submitBirthdayLeave(User $user, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate, string $reason): LeaveRequest
    {
        self::checkPayrollLock($user, $startDate, $endDate);
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
                'total_days' => 1.0,
                'is_half_day' => false,
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

            $current = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->startOfDay();
            while ($current->lte($end)) {
                event(new \App\Events\AttendanceOverridden($lockedUser, $current->copy(), $lockedUser));
                $current->addDay();
            }

            return $request;
        });
    }

    /**
     * Centralized Leave Balance Adjuster and Ledger Recorder.
     */
    public static function adjustBalance(User $user, float $amount, string $type, string $description, ?int $leaveRequestId = null): void
    {
        DB::transaction(function () use ($user, $amount, $type, $description, $leaveRequestId) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            
            $lockedUser->leave_balance += $amount;
            $lockedUser->save();
            
            $lb = LeaveBalance::where('user_id', $lockedUser->id)->lockForUpdate()->first();
            if ($lb) {
                $lb->utilized_leave = max(0.0, $lb->utilized_leave - $amount);
                $lb->remaining_leave = $lockedUser->leave_balance;
                $lb->saveQuietly();
            }
            
            LeaveLedgerEntry::create([
                'user_id' => $lockedUser->id,
                'leave_request_id' => $leaveRequestId,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
            ]);
        });
    }

    /**
     * Shared helper to validate leave balance.
     */
    protected static function validateLeaveBalance(User $user, float $totalDays, string $leaveType): void
    {
        if ($user->leave_balance < $totalDays) {
            throw new \Exception("Insufficient leave balance. You have {$user->leave_balance} days available, but requested {$totalDays} days.");
        }
    }

    /**
     * Submit/Apply for leave with comprehensive validations.
     */
    public static function applyRequest(User $user, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $startDate = \Carbon\Carbon::parse($data['start_date'])->startOfDay();
            $endDate = \Carbon\Carbon::parse($data['end_date'])->startOfDay();
            self::checkPayrollLock($user, $startDate, $endDate);
            $isHalfDay = filter_var($data['is_half_day'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $totalDays = $isHalfDay ? 0.5 : ((int)$startDate->diffInDays($endDate) + 1);

            // Date validations
            if ($data['leave_type'] === 'planned') {
                if ($startDate->lt(\Carbon\Carbon::today())) {
                    throw new \Exception('Planned Leave may only be requested for Today or Future dates.');
                }
            } elseif ($data['leave_type'] === 'unplanned') {
                if ($startDate->gt(\Carbon\Carbon::today()) || $endDate->gt(\Carbon\Carbon::today())) {
                    throw new \Exception('Unplanned Leave may only be requested for Past or Today dates.');
                }
            }

            // Overlap Validation
            $overlap = LeaveRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_date', '<=', $endDate->format('Y-m-d'))
                ->where('end_date', '>=', $startDate->format('Y-m-d'))
                ->exists();

            if ($overlap) {
                throw new \Exception('You already have a pending or approved leave request that overlaps with this date range.');
            }

            // Birthday leave validation
            if ($data['leave_type'] === 'complimentary') {
                if ((float)$totalDays !== 1.0) {
                    throw new \Exception('Birthday Leave must be exactly 1 day.');
                }
                if (!$user->employeeProfile || !$user->employeeProfile->date_of_birth) {
                    throw new \Exception('You are not eligible for Birthday Leave (Date of Birth is not set).');
                }
                if (!\App\Models\User::canUseBirthdayLeave($user, \Carbon\Carbon::today()) ||
                    !\App\Models\User::canUseBirthdayLeave($user, $startDate)) {
                    throw new \Exception('Birthday Leave credit is not available, locked, or has expired for this date.');
                }
                $available = $user->getAvailableBirthdayYears($startDate);
                if (empty($available)) {
                    throw new \Exception('Birthday Leave credit is not available, locked, or has expired for this date.');
                }

                return self::submitBirthdayLeave($user, $startDate, $endDate, $data['reason']);
            }

            // Leave Balance Validation
            if (in_array($data['leave_type'], ['planned', 'unplanned'])) {
                self::validateLeaveBalance($user, $totalDays, $data['leave_type']);
            }

            $isPaid = ($data['leave_type'] === 'planned' || $data['leave_type'] === 'complimentary');

            $status = ($user->role === 'admin') ? 'approved' : 'pending';
            $approverId = ($user->role === 'admin') ? $user->id : null;
            $approvedAt = ($user->role === 'admin') ? now() : null;
            
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type' => $data['leave_type'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'is_half_day' => $isHalfDay,
                'reason' => $data['reason'],
                'status' => $status,
                'approver_id' => $approverId,
                'approved_at' => $approvedAt,
                'is_paid' => $isPaid,
            ]);

            if ($status === 'approved') {
                if ($data['leave_type'] === 'planned') {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                    $lockedUser->leave_balance -= $totalDays;
                    $lockedUser->save();
                    
                    $lb = LeaveBalance::where('user_id', $lockedUser->id)->lockForUpdate()->first();
                    if ($lb) {
                        $lb->utilized_leave += $totalDays;
                        $lb->remaining_leave = $lockedUser->leave_balance;
                        $lb->save();
                    }
                    
                    LeaveLedgerEntry::create([
                        'user_id' => $lockedUser->id,
                        'leave_request_id' => $leaveRequest->id,
                        'amount' => -$totalDays,
                        'type' => 'deduction',
                        'description' => 'Leave approved: ' . ucfirst($data['leave_type']) . ' Leave' . ($isHalfDay ? ' (Half Day)' : ''),
                    ]);
                } elseif ($data['leave_type'] === 'unplanned') {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                    LeaveLedgerEntry::create([
                        'user_id' => $lockedUser->id,
                        'leave_request_id' => $leaveRequest->id,
                        'amount' => 0.00,
                        'type' => 'deduction',
                        'description' => 'Leave approved: ' . ucfirst($data['leave_type']) . ' Leave (Unpaid)' . ($isHalfDay ? ' (Half Day)' : ''),
                    ]);
                }
            }

            LeaveRequestLog::create([
                'leave_request_id' => $leaveRequest->id,
                'from_status' => null,
                'to_status' => $status === 'approved' ? 'pending' : $status,
                'action' => 'applied',
                'notes' => 'Applied.',
                'user_id' => $user->id,
            ]);

            if ($status === 'approved') {
                LeaveRequestLog::create([
                    'leave_request_id' => $leaveRequest->id,
                    'from_status' => 'pending',
                    'to_status' => 'approved',
                    'action' => 'approved',
                    'notes' => 'Auto-approved for admin.',
                    'user_id' => $user->id,
                ]);

                $current = \Carbon\Carbon::parse($startDate)->startOfDay();
                $end = \Carbon\Carbon::parse($endDate)->startOfDay();
                while ($current->lte($end)) {
                    event(new \App\Events\AttendanceOverridden($user, $current->copy(), $user));
                    $current->addDay();
                }
            }

            return $leaveRequest;
        });
    }

    /**
     * Approve leave request.
     */
    public static function approveRequest(LeaveRequest $leaveRequest, User $approver, ?string $notes = null): void
    {
        self::checkPayrollLock($leaveRequest->user, $leaveRequest->start_date, $leaveRequest->end_date);
        DB::transaction(function () use ($leaveRequest, $approver, $notes) {
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();
            if ($lockedRequest->status !== 'pending') {
                throw new \Exception('Only pending requests can be approved.');
            }

            $applicant = $lockedRequest->user;
            $totalDays = $lockedRequest->total_days;

            $isPaid = ($lockedRequest->leave_type === 'planned');
            $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->firstOrFail();

            // Update status
            $lockedRequest->update([
                'status' => 'approved',
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            // Balance Check for both planned and unplanned
            if (in_array($lockedRequest->leave_type, ['planned', 'unplanned'])) {
                self::validateLeaveBalance($lockedUser, $totalDays, $lockedRequest->leave_type);
            }

            if ($isPaid) {

                // Deduct balance
                self::adjustBalance(
                    $lockedUser,
                    -$totalDays,
                    'deduction',
                    'Leave approved: ' . ucfirst($lockedRequest->leave_type) . ' Leave' . ($lockedRequest->is_half_day ? ' (Half Day)' : ''),
                    $lockedRequest->id
                );
            } else {
                self::adjustBalance(
                    $lockedUser,
                    0.00,
                    'deduction',
                    'Leave approved: ' . ucfirst($lockedRequest->leave_type) . ' Leave' . ($lockedRequest->is_half_day ? ' (Half Day)' : ''),
                    $lockedRequest->id
                );
            }

            LeaveRequestLog::create([
                'leave_request_id' => $lockedRequest->id,
                'from_status' => 'pending',
                'to_status' => 'approved',
                'action' => 'approved',
                'notes' => $notes ?? 'Approved by manager/admin.',
                'user_id' => $approver->id,
            ]);

            $current = \Carbon\Carbon::parse($lockedRequest->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($lockedRequest->end_date)->startOfDay();
            while ($current->lte($end)) {
                event(new \App\Events\AttendanceOverridden($applicant, $current->copy(), $approver));
                $current->addDay();
            }
        });
    }

    /**
     * Reject leave request.
     */
    public static function rejectRequest(LeaveRequest $leaveRequest, User $rejecter, string $reason): void
    {
        self::checkPayrollLock($leaveRequest->user, $leaveRequest->start_date, $leaveRequest->end_date);
        DB::transaction(function () use ($leaveRequest, $rejecter, $reason) {
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();
            if (!in_array($lockedRequest->status, ['pending', 'approved'])) {
                throw new \Exception('Only pending or approved requests can be rejected.');
            }

            $oldStatus = $lockedRequest->status;

            $lockedRequest->update([
                'status' => 'rejected',
                'approver_id' => $rejecter->id,
                'approved_at' => null,
                'rejection_reason' => $reason,
            ]);

            if ($oldStatus === 'approved') {
                $applicant = $lockedRequest->user;
                if ($lockedRequest->leave_type === 'complimentary') {
                    $credit = $lockedRequest->leaveCredit;
                    if ($credit) {
                        $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                        if ($lockedCredit) {
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                    }
                    $lockedRequest->update(['leave_credit_id' => null]);
                } else {
                    self::adjustBalance(
                        $applicant,
                        $lockedRequest->total_days,
                        'refund',
                        'Refund due to leave rejection of approved leave request: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                        $lockedRequest->id
                    );
                }
            }

            LeaveRequestLog::create([
                'leave_request_id' => $lockedRequest->id,
                'from_status' => $oldStatus,
                'to_status' => 'rejected',
                'action' => 'rejected',
                'notes' => $reason,
                'user_id' => $rejecter->id,
            ]);

            $current = \Carbon\Carbon::parse($lockedRequest->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($lockedRequest->end_date)->startOfDay();
            while ($current->lte($end)) {
                event(new \App\Events\AttendanceOverridden($lockedRequest->user, $current->copy(), $rejecter));
                $current->addDay();
            }
        });
    }

    public static function cancelRequest(LeaveRequest $leaveRequest, User $user): void
    {
        self::checkPayrollLock($leaveRequest->user, $leaveRequest->start_date, $leaveRequest->end_date);
        DB::transaction(function () use ($leaveRequest, $user) {
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();
            if (!in_array($lockedRequest->status, ['pending', 'approved'])) {
                throw new \Exception('Only pending or approved requests can be cancelled.');
            }

            $oldStatus = $lockedRequest->status;

            $lockedRequest->update([
                'status' => 'cancelled',
            ]);

            if ($oldStatus === 'approved') {
                $applicant = $lockedRequest->user;
                if ($lockedRequest->leave_type === 'complimentary') {
                    $credit = $lockedRequest->leaveCredit;
                    if ($credit) {
                        $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                        if ($lockedCredit) {
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                    }
                    $lockedRequest->update(['leave_credit_id' => null]);
                } else {
                    $isPaid = ($lockedRequest->leave_type === 'planned');
                    self::adjustBalance(
                        $applicant,
                        $isPaid ? $lockedRequest->total_days : 0.00,
                        'refund',
                        'Refund for cancelled leave: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                        $lockedRequest->id
                    );
                }
            }

            LeaveRequestLog::create([
                'leave_request_id' => $lockedRequest->id,
                'from_status' => $oldStatus,
                'to_status' => 'cancelled',
                'action' => 'cancelled',
                'notes' => 'Cancelled by applicant.',
                'user_id' => $user->id,
            ]);

            $current = \Carbon\Carbon::parse($lockedRequest->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($lockedRequest->end_date)->startOfDay();
            while ($current->lte($end)) {
                event(new \App\Events\AttendanceOverridden($lockedRequest->user, $current->copy(), $user));
                $current->addDay();
            }
        });
    }

    /**
     * Administrative decision override.
     */
    public static function overrideRequest(LeaveRequest $leaveRequest, User $admin, string $status, string $notes): void
    {
        self::checkPayrollLock($leaveRequest->user, $leaveRequest->start_date, $leaveRequest->end_date);
        DB::transaction(function () use ($leaveRequest, $admin, $status, $notes) {
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedRequest->status;

            if ($status === $oldStatus) {
                return;
            }

            $applicant = $lockedRequest->user;
            $totalDays = $lockedRequest->total_days;

            $updateData = [
                'status' => $status,
                'approver_id' => $admin->id,
                'approved_at' => $status === 'approved' ? now() : null,
            ];

            if ($status === 'approved') {
                $updateData['notes'] = $notes;
                $updateData['rejection_reason'] = null;
            } else {
                $updateData['rejection_reason'] = $notes;
                $updateData['notes'] = null;
            }

            $lockedRequest->update($updateData);

            if ($oldStatus === 'approved' && $status !== 'approved') {
                if ($lockedRequest->leave_type === 'complimentary') {
                    $credit = $lockedRequest->leaveCredit;
                    if ($credit) {
                        $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                        if ($lockedCredit) {
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                    }
                    $lockedRequest->update(['leave_credit_id' => null]);
                } else {
                    $isPaid = ($lockedRequest->leave_type === 'planned');
                    self::adjustBalance(
                        $applicant,
                        $isPaid ? $totalDays : 0.00,
                        'refund',
                        'Refund due to admin override/reclassification of approved leave',
                        $lockedRequest->id
                    );
                }
            }
            elseif ($oldStatus !== 'approved' && $status === 'approved') {
                if ($lockedRequest->leave_type === 'complimentary') {
                    $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->firstOrFail();
                    $available = $lockedUser->getAvailableBirthdayYears($lockedRequest->start_date);
                    if (empty($available)) {
                        throw new \Exception('Birthday Leave credit is not available or locked.');
                    }
                    $selectedCredit = $available[0];
                    $lockedCredit = LeaveCredit::where('id', $selectedCredit['credit_id'])->lockForUpdate()->first();
                    $lockedCredit->used_amount = $lockedCredit->amount;
                    $lockedCredit->save();

                    $lockedRequest->update([
                        'leave_credit_id' => $lockedCredit->id,
                        'is_paid' => true,
                        'metadata' => ['is_birthday' => true],
                    ]);
                } else {
                    $isPaid = ($lockedRequest->leave_type === 'planned');
                    $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->firstOrFail();
                    if ($isPaid) {
                        if ($lockedUser->leave_balance < $totalDays) {
                            throw new \Exception('Insufficient leave balance.');
                        }
                        self::adjustBalance(
                            $lockedUser,
                            -$totalDays,
                            'deduction',
                            'Leave approved via admin override: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                            $lockedRequest->id
                        );
                    } else {
                        self::adjustBalance(
                            $lockedUser,
                            0.00,
                            'deduction',
                            'Leave approved via admin override: ' . ucfirst($lockedRequest->leave_type) . ' Leave (Unpaid)',
                            $lockedRequest->id
                        );
                    }
                }
            }

            LeaveRequestLog::create([
                'leave_request_id' => $lockedRequest->id,
                'from_status' => $oldStatus,
                'to_status' => $status,
                'action' => 'overridden',
                'notes' => $notes,
                'user_id' => $admin->id,
            ]);

            $current = \Carbon\Carbon::parse($lockedRequest->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($lockedRequest->end_date)->startOfDay();
            while ($current->lte($end)) {
                event(new \App\Events\AttendanceOverridden($applicant, $current->copy(), $admin));
                $current->addDay();
            }
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
                'salary_effective_date' => $user->joining_date ? $user->joining_date->format('Y-m-d') : null,
                'payroll_enabled' => true,
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

    public static function updateLeaveBalance(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $lb = LeaveBalance::where('user_id', $lockedUser->id)->lockForUpdate()->firstOrCreate([
                'user_id' => $lockedUser->id
            ], [
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

            $fields = [
                'planned_leave',
                'unplanned_leave',
                'paternity_leave',
                'maternity_leave',
                'compensatory_leave',
                'carry_forward',
                'pending_leave',
                'utilized_leave',
                'remaining_leave',
            ];

            $changes = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $newVal = (float) $data[$field];
                    $oldVal = (float) ($lb->$field ?? 0.00);
                    if ($newVal !== $oldVal) {
                        $lb->$field = $newVal;
                        $changes[] = ucfirst(str_replace('_', ' ', $field)) . " updated from {$oldVal} to {$newVal}";
                    }
                }
            }

            if (isset($data['birthday_leave'])) {
                $year = now()->year;
                $identifier = "birthday_{$year}";
                $credit = LeaveCredit::where('user_id', $lockedUser->id)
                    ->where('source_identifier', $identifier)
                    ->first();
                if ($credit) {
                    $newBdayVal = (float) $data['birthday_leave'];
                    $oldBdayVal = (float) ($credit->amount - $credit->used_amount);
                    if ($newBdayVal !== $oldBdayVal) {
                        $credit->used_amount = max(0.00, $credit->amount - $newBdayVal);
                        if ($newBdayVal > 0 && $credit->status !== 'active') {
                            $credit->status = 'active';
                        }
                        $credit->save();
                        $changes[] = "Birthday Leave updated from {$oldBdayVal} to {$newBdayVal}";
                    }
                }
            }

            // Recalculate remaining leave only if it is not explicitly provided in the data
            if (!isset($data['remaining_leave'])) {
                $calculatedRemaining = (float) (
                    $lb->planned_leave +
                    $lb->unplanned_leave +
                    $lb->paternity_leave +
                    $lb->maternity_leave +
                    $lb->compensatory_leave +
                    $lb->carry_forward -
                    $lb->utilized_leave
                );

                $oldRemaining = (float) ($lb->remaining_leave ?? 0.00);
                if ($oldRemaining !== $calculatedRemaining) {
                    $lb->remaining_leave = $calculatedRemaining;
                    $changes[] = "Remaining leave updated from {$oldRemaining} to {$calculatedRemaining} (calculated)";
                }
            }

            if (!empty($changes)) {
                $lb->save();

                $targetRemaining = (float) $lb->remaining_leave;
                if ((float)$lockedUser->leave_balance !== $targetRemaining) {
                    $lockedUser->leave_balance = $targetRemaining;
                    $lockedUser->save();
                }

                LeaveLedgerEntry::create([
                    'user_id' => $lockedUser->id,
                    'amount' => 0.00,
                    'type' => 'adjustment',
                    'description' => 'Administrative adjustment: ' . implode(', ', $changes),
                ]);
            }
        });
    }

    /**
     * Accrue monthly leaves for all active employees.
     */
    public static function accrueMonthlyLeaves(): int
    {
        $users = User::where('status', 'active')
            ->whereIn('role', ['employee', 'manager'])
            ->get();

        $count = 0;
        $monthYear = now()->format('F Y');
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        foreach ($users as $user) {
            // Check if user already has an accrual for this calendar month
            $alreadyAccrued = LeaveLedgerEntry::where('user_id', $user->id)
                ->where('type', 'accrual')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->exists();

            if ($alreadyAccrued) {
                continue;
            }

            DB::transaction(function () use ($user, $monthYear) {
                $lb = LeaveBalance::where('user_id', $user->id)->lockForUpdate()->firstOrCreate([
                    'user_id' => $user->id
                ], [
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

                // add 2 days to remaining_leave
                $lb->remaining_leave += 2.00;
                $lb->save();

                // Ensure user's cached leave balance is updated
                $user->refresh();
                if ((float)$user->leave_balance !== (float)$lb->remaining_leave) {
                    $user->leave_balance = $lb->remaining_leave;
                    $user->save();
                }

                // create a Leave Ledger entry
                LeaveLedgerEntry::create([
                    'user_id' => $user->id,
                    'amount' => 2.00,
                    'type' => 'accrual',
                    'description' => "Monthly accrual for {$monthYear}",
                ]);

                // create an Audit Trail entry (system log)
                \Illuminate\Support\Facades\Log::info("Monthly leave accrual processed for user ID: {$user->id} (Employee ID: {$user->employee_id}), added 2.00 days.");
            });

            $count++;
        }

        return $count;
    }
}
