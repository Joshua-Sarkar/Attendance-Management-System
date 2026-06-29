<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveRequestLog;
use App\Models\User;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveCredit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of leave requests and summary statistics.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Dynamically sync birthday credits
        $user->syncBirthdayCredits();

        // Own leaves history
        $myLeaves = LeaveRequest::where('user_id', $user->id)
            ->with(['approver'])
            ->orderBy('start_date', 'desc')
            ->get();

        // Summary statistics of own approved leaves for current year
        $yearStart = Carbon::now()->startOfYear();
        $yearEnd = Carbon::now()->endOfYear();
        $myApprovedLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '>=', $yearStart)
            ->where('start_date', '<=', $yearEnd)
            ->get();

        $stats = [
            'planned' => $myApprovedLeaves->where('leave_type', 'planned')->sum('total_days'),
            'unplanned' => $myApprovedLeaves->where('leave_type', 'unplanned')->sum('total_days'),
            'complimentary' => $myApprovedLeaves->where('leave_type', 'complimentary')->sum('total_days'),
            'total_approved' => $myApprovedLeaves->sum('total_days'),
        ];

        // Approval Queue and History based on role
        $pendingQueue = collect();
        $historyQueue = collect();

        if ($user->role === 'admin') {
            // Admin sees all pending and history (excluding self requests in the approval queue)
            $pendingQueue = LeaveRequest::where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            $historyQueue = LeaveRequest::where('status', '!=', 'pending')
                ->with(['user', 'approver'])
                ->orderBy('updated_at', 'desc')
                ->get();
        } elseif ($user->role === 'manager') {
            // Manager sees only pending and history for assigned employees
            $pendingQueue = LeaveRequest::where('status', 'pending')
                ->whereHas('user', function ($q) use ($user) {
                    $q->where('role', 'employee')->where('manager_id', $user->id);
                })
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            $historyQueue = LeaveRequest::where('status', '!=', 'pending')
                ->whereHas('user', function ($q) use ($user) {
                    $q->where('role', 'employee')->where('manager_id', $user->id);
                })
                ->with(['user', 'approver'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return view('leaves.index', compact('myLeaves', 'stats', 'pendingQueue', 'historyQueue'));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create(): View
    {
        $user = Auth::user();
        $user->syncBirthdayCredits();
        $hasBirthdayCredit = !empty($user->getAvailableBirthdayYears());
        return view('leaves.create', compact('hasBirthdayCredit'));
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
            'leave_type' => 'required|string|in:planned,unplanned,complimentary',
        ];

        $validated = $request->validate($rules);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->startOfDay();
        $totalDays = (int) $startDate->diffInDays($endDate) + 1;

        if ($validated['leave_type'] === 'complimentary' && $totalDays !== 1) {
            return back()->withErrors(['end_date' => 'Birthday Leave must be exactly 1 day.'])->withInput();
        }

        // Overlap Validation
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            return back()->withErrors(['start_date' => 'You already have a pending or approved leave request that overlaps with this date range.'])->withInput();
        }

        // Handle Complimentary / Birthday Leave (Auto-approved for everyone)
        if ($validated['leave_type'] === 'complimentary') {
            try {
                $leaveRequest = \App\Services\LeaveBalanceService::submitBirthdayLeave($user, $startDate, $endDate, $validated['reason']);
            } catch (\Exception $e) {
                return back()->withErrors(['leave_type' => $e->getMessage()])->withInput();
            }

            return redirect()->route('leaves.index')->with('success', 'Birthday Leave submitted and automatically approved.');
        }

        // Determine status based on approval hierarchy (Admins auto-approved for Planned/Unplanned)
        if ($user->role === 'admin') {
            if ($user->leave_balance < $totalDays) {
                return back()->withErrors(['start_date' => "Insufficient leave balance. You have {$user->leave_balance} days available, but requested {$totalDays} days."])->withInput();
            }

            try {
                $leaveRequest = DB::transaction(function () use ($user, $validated, $startDate, $endDate, $totalDays) {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                    if ($lockedUser->leave_balance < $totalDays) {
                        throw new \Exception("Insufficient leave balance.");
                    }

                    $request = LeaveRequest::create([
                        'user_id' => $lockedUser->id,
                        'leave_type' => $validated['leave_type'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'total_days' => $totalDays,
                        'reason' => $validated['reason'],
                        'status' => 'approved',
                        'approver_id' => $lockedUser->id,
                        'approved_at' => now(),
                        'notes' => 'Auto-approved for Admin user.',
                    ]);

                    $lockedUser->leave_balance -= $totalDays;
                    $lockedUser->save();

                    LeaveLedgerEntry::create([
                        'user_id' => $lockedUser->id,
                        'leave_request_id' => $request->id,
                        'amount' => -$totalDays,
                        'type' => 'deduction',
                        'description' => 'Leave approved: ' . ucfirst($validated['leave_type']) . ' Leave',
                    ]);

                    // Double log to maintain full audit trail
                    LeaveRequestLog::create([
                        'leave_request_id' => $request->id,
                        'from_status' => null,
                        'to_status' => 'pending',
                        'action' => 'applied',
                        'notes' => 'Applied by Admin.',
                        'user_id' => $lockedUser->id,
                    ]);

                    LeaveRequestLog::create([
                        'leave_request_id' => $request->id,
                        'from_status' => 'pending',
                        'to_status' => 'approved',
                        'action' => 'approved',
                        'notes' => 'Automatically approved.',
                        'user_id' => $lockedUser->id,
                    ]);

                    return $request;
                });
            } catch (\Exception $e) {
                return back()->withErrors(['start_date' => $e->getMessage()])->withInput();
            }

            return redirect()->route('leaves.index')->with('success', 'Leave request submitted and auto-approved successfully.');
        }

        // Managers and Employees start as pending
        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        LeaveRequestLog::create([
            'leave_request_id' => $leaveRequest->id,
            'from_status' => null,
            'to_status' => 'pending',
            'action' => 'applied',
            'notes' => 'Applied.',
            'user_id' => $user->id,
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Display the specified leave request and its audit logs.
     */
    public function show(LeaveRequest $leaveRequest): View
    {
        $user = Auth::user();

        // Access Control checks
        if ($user->role === 'employee' && $leaveRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->role === 'manager') {
            // Managers can view their own requests OR requests of employees assigned to them
            $isOwn = $leaveRequest->user_id === $user->id;
            $isAssigned = $leaveRequest->user->role === 'employee' && $leaveRequest->user->manager_id === $user->id;
            if (!$isOwn && !$isAssigned) {
                abort(403, 'Unauthorized access.');
            }
        }

        $logs = $leaveRequest->logs()->with('user')->orderBy('created_at', 'asc')->get();

        return view('leaves.show', compact('leaveRequest', 'logs'));
    }

    /**
     * Cancel a leave request (pending or approved, submitted by self).
     */
    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();

        // Only the owner can cancel their request
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'You cannot cancel someone else\'s leave request.');
        }

        // Status must be pending or approved
        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'Only pending or approved requests can be cancelled.');
        }

        try {
            DB::transaction(function () use ($leaveRequest, $user) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                if (!in_array($lockedRequest->status, ['pending', 'approved'])) {
                    throw new \Exception('Only pending or approved requests can be cancelled.');
                }

                $oldStatus = $lockedRequest->status;

                $lockedRequest->update([
                    'status' => 'cancelled',
                ]);

                if ($oldStatus === 'approved') {
                    if ($lockedRequest->leave_type === 'complimentary') {
                        // Restore birthday leave credit
                        $credit = $lockedRequest->leaveCredit;
                        if ($credit) {
                            $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                        $lockedRequest->update(['leave_credit_id' => null]);
                    } else {
                        // Refund regular leave balance
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                        $lockedUser->leave_balance += $lockedRequest->total_days;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $lockedRequest->id,
                            'amount' => $lockedRequest->total_days,
                            'type' => 'refund',
                            'description' => 'Refund for cancelled leave: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                        ]);
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
            });
        } catch (\Exception $e) {
            return redirect()->route('leaves.index')->with('error', 'Cancellation failed: ' . $e->getMessage());
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled successfully.');
    }

    /**
     * Approve a leave request (Manager or Admin only).
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();

        // Request must be pending
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        // Self-action protection
        if ($leaveRequest->user_id === $user->id) {
            return back()->with('error', 'You cannot approve your own leave request.');
        }

        // Authorization checks
        if ($user->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        if ($user->role === 'manager') {
            // Managers can only approve requests of employees assigned to them
            $isAssignedEmployee = $leaveRequest->user->role === 'employee' && $leaveRequest->user->manager_id === $user->id;
            if (!$isAssignedEmployee) {
                abort(403, 'You can only approve requests for employees assigned to you.');
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $applicant = $leaveRequest->user;

        // Balance Check for planned/unplanned leaves
        if ($leaveRequest->leave_type !== 'complimentary' && $applicant->leave_balance < $leaveRequest->total_days) {
            return back()->with('error', "Insufficient leave balance. Employee has {$applicant->leave_balance} days available, but requested {$leaveRequest->total_days} days.");
        }

        try {
            DB::transaction(function () use ($leaveRequest, $user, $applicant, $validated) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                if ($lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $lockedRequest->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($lockedRequest->leave_type !== 'complimentary') {
                    $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->first();
                    if ($lockedUser->leave_balance < $lockedRequest->total_days) {
                        throw new \Exception('Insufficient leave balance.');
                    }
                    
                    $lockedUser->leave_balance -= $lockedRequest->total_days;
                    $lockedUser->save();

                    LeaveLedgerEntry::create([
                        'user_id' => $lockedUser->id,
                        'leave_request_id' => $lockedRequest->id,
                        'amount' => -$lockedRequest->total_days,
                        'type' => 'deduction',
                        'description' => 'Leave approved: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                    ]);
                }

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => 'pending',
                    'to_status' => 'approved',
                    'action' => 'approved',
                    'notes' => $validated['notes'] ?? 'Approved by manager/admin.',
                    'user_id' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('leaves.index')->with('error', 'Approval failed: ' . $e->getMessage());
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request approved successfully.');
    }

    /**
     * Reject a leave request (Manager or Admin only).
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();

        // Request must be pending or approved
        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'Only pending or approved requests can be rejected.');
        }

        // Self-action protection
        if ($leaveRequest->user_id === $user->id) {
            return back()->with('error', 'You cannot reject your own leave request.');
        }

        // Authorization checks
        if ($user->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        if ($user->role === 'manager') {
            // Managers can only reject requests of employees assigned to them
            $isAssignedEmployee = $leaveRequest->user->role === 'employee' && $leaveRequest->user->manager_id === $user->id;
            if (!$isAssignedEmployee) {
                abort(403, 'You can only reject requests for employees assigned to you.');
            }
        }

        // Validation for rejection reason
        $request->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        try {
            DB::transaction(function () use ($leaveRequest, $user, $request) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                if (!in_array($lockedRequest->status, ['pending', 'approved'])) {
                    throw new \Exception('Only pending or approved requests can be rejected.');
                }

                $oldStatus = $lockedRequest->status;

                $lockedRequest->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'approved_at' => null,
                    'rejection_reason' => $request->input('rejection_reason'),
                ]);

                $applicant = $lockedRequest->user;
                $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->first();

                // If it was approved, refund the balance / restore credit
                if ($oldStatus === 'approved') {
                    if ($lockedRequest->leave_type === 'complimentary') {
                        // Restore birthday leave credit
                        $credit = $lockedRequest->leaveCredit;
                        if ($credit) {
                            $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                        $lockedRequest->update(['leave_credit_id' => null]);
                    } else {
                        // Refund regular balance
                        $lockedUser->leave_balance += $lockedRequest->total_days;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $lockedRequest->id,
                            'amount' => $lockedRequest->total_days,
                            'type' => 'refund',
                            'description' => 'Refund due to leave rejection of approved leave request',
                        ]);
                    }
                }

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => $oldStatus,
                    'to_status' => 'rejected',
                    'action' => 'rejected',
                    'notes' => $request->input('rejection_reason'),
                    'user_id' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('leaves.index')->with('error', 'Rejection failed: ' . $e->getMessage());
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request rejected.');
    }

    /**
     * Admin-only override of an existing leave request decision.
     */
    public function override(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();

        // Enforce Admin-only restriction
        if ($user->role !== 'admin') {
            abort(403, 'Only admins can override decisions.');
        }

        // Self-action protection
        if ($leaveRequest->user_id === $user->id) {
            return back()->with('error', 'You cannot override your own leave request.');
        }

        $request->validate([
            'override_status' => 'required|string|in:approved,rejected',
            'override_notes' => 'required|string|min:5',
        ]);

        $newOverrideStatus = $request->input('override_status');
        $notes = $request->input('override_notes');
        $applicant = $leaveRequest->user;

        $wasApproved = ($leaveRequest->status === 'approved');
        $shouldBeApproved = ($newOverrideStatus === 'approved');

        // Validation for Planned/Unplanned Leave balance checking
        if (!$wasApproved && $shouldBeApproved && $leaveRequest->leave_type !== 'complimentary') {
            if ($applicant->leave_balance < $leaveRequest->total_days) {
                return back()->with('error', "Insufficient leave balance. Employee has {$applicant->leave_balance} days available, but requested {$leaveRequest->total_days} days.");
            }
        }

        // Validation for Complimentary credit checking
        if (!$wasApproved && $shouldBeApproved && $leaveRequest->leave_type === 'complimentary') {
            $available = $applicant->getAvailableBirthdayYears($leaveRequest->start_date);
            if (empty($available)) {
                return back()->with('error', 'Birthday Leave credit is not available or locked for this date.');
            }
        }

        try {
            DB::transaction(function () use ($leaveRequest, $user, $applicant, $newOverrideStatus, $wasApproved, $shouldBeApproved, $notes) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                $oldStatus = $lockedRequest->status;

                $updateData = [
                    'status' => $newOverrideStatus,
                    'approver_id' => $user->id,
                    'approved_at' => $shouldBeApproved ? now() : null,
                ];

                if ($shouldBeApproved) {
                    $updateData['notes'] = $notes;
                    $updateData['rejection_reason'] = null;
                } else {
                    $updateData['rejection_reason'] = $notes;
                    $updateData['notes'] = null;
                }

                $lockedRequest->update($updateData);

                $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->first();

                // State Transitions
                // Case 1: Was approved, now rejected (Refund or Restore Credit)
                if ($wasApproved && !$shouldBeApproved) {
                    if ($lockedRequest->leave_type === 'complimentary') {
                        // Restore birthday leave credit
                        $credit = $lockedRequest->leaveCredit;
                        if ($credit) {
                            $lockedCredit = LeaveCredit::where('id', $credit->id)->lockForUpdate()->first();
                            $lockedCredit->used_amount = 0.00;
                            $lockedCredit->save();
                        }
                        $lockedRequest->update(['leave_credit_id' => null]);
                    } else {
                        // Refund regular balance
                        $lockedUser->leave_balance += $lockedRequest->total_days;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $lockedRequest->id,
                            'amount' => $lockedRequest->total_days,
                            'type' => 'refund',
                            'description' => 'Refund due to admin override/reclassification of approved leave',
                        ]);
                    }
                }
                // Case 2: Was NOT approved, now approved (Deduct or Consume Credit)
                elseif (!$wasApproved && $shouldBeApproved) {
                    if ($lockedRequest->leave_type === 'complimentary') {
                        // Consume birthday leave credit
                        $available = $lockedUser->getAvailableBirthdayYears($lockedRequest->start_date);
                        if (empty($available)) {
                            throw new \Exception('Birthday Leave credit is not available or locked.');
                        }
                        $selectedCredit = $available[0];
                        $lockedCredit = LeaveCredit::where('id', $selectedCredit['credit_id'])->lockForUpdate()->first();
                        $lockedCredit->used_amount = $lockedCredit->amount;
                        $lockedCredit->save();

                        $lockedRequest->update(['leave_credit_id' => $lockedCredit->id]);
                    } else {
                        // Deduct regular balance
                        if ($lockedUser->leave_balance < $lockedRequest->total_days) {
                            throw new \Exception('Insufficient leave balance.');
                        }
                        $lockedUser->leave_balance -= $lockedRequest->total_days;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $lockedRequest->id,
                            'amount' => -$lockedRequest->total_days,
                            'type' => 'deduction',
                            'description' => 'Leave approved via admin override: ' . ucfirst($lockedRequest->leave_type) . ' Leave',
                        ]);
                    }
                }

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newOverrideStatus,
                    'action' => 'overridden',
                    'notes' => $notes,
                    'user_id' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('leaves.index')->with('error', 'Override failed: ' . $e->getMessage());
        }

        return redirect()->route('leaves.index')->with('success', 'Leave decision overridden successfully.');
    }
}
