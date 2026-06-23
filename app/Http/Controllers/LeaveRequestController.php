<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveRequestLog;
use App\Models\User;
use App\Models\LeaveLedgerEntry;
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
            'casual_leave' => $myApprovedLeaves->where('leave_type', 'casual_leave')->sum('total_days'),
            'sick_leave' => $myApprovedLeaves->where('leave_type', 'sick_leave')->sum('total_days'),
            'paid_leave' => $myApprovedLeaves->where('leave_type', 'paid_leave')->sum('total_days'),
            'unpaid_leave' => $myApprovedLeaves->where('leave_type', 'unpaid_leave')->sum('total_days'),
            'work_from_home' => $myApprovedLeaves->where('leave_type', 'work_from_home')->sum('total_days'),
            'emergency_leave' => $myApprovedLeaves->where('leave_type', 'emergency_leave')->sum('total_days'),
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
        return view('leaves.create');
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'leave_type' => 'required|string|in:casual_leave,sick_leave,paid_leave,unpaid_leave,work_from_home,emergency_leave',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Overlap Validation
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            return back()->withErrors(['start_date' => 'You already have a pending or approved leave request that overlaps with this date range.'])->withInput();
        }

        // Determine status based on approval hierarchy
        if ($user->role === 'admin') {
            $isDeductible = !in_array($validated['leave_type'], ['unpaid_leave', 'work_from_home']);
            if ($isDeductible && $user->leave_balance < $totalDays) {
                return back()->withErrors(['start_date' => "Insufficient leave balance. You have {$user->leave_balance} days available, but requested {$totalDays} days."])->withInput();
            }

            try {
                $leaveRequest = DB::transaction(function () use ($user, $validated, $startDate, $endDate, $totalDays, $isDeductible) {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                    if ($isDeductible && $lockedUser->leave_balance < $totalDays) {
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

                    if ($isDeductible) {
                        $lockedUser->leave_balance -= $totalDays;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $request->id,
                            'amount' => -$totalDays,
                            'type' => 'deduction',
                            'description' => 'Leave approved: ' . ucfirst(str_replace('_', ' ', $validated['leave_type'])),
                        ]);
                    }

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

        $isDeductible = !in_array($leaveRequest->leave_type, ['unpaid_leave', 'work_from_home']);

        try {
            DB::transaction(function () use ($leaveRequest, $user, $isDeductible) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                if (!in_array($lockedRequest->status, ['pending', 'approved'])) {
                    throw new \Exception('Only pending or approved requests can be cancelled.');
                }

                $oldStatus = $lockedRequest->status;

                $lockedRequest->update([
                    'status' => 'cancelled',
                ]);

                if ($oldStatus === 'approved' && $isDeductible) {
                    // Refund leave balance
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                    $lockedUser->leave_balance += $lockedRequest->total_days;
                    $lockedUser->save();

                    LeaveLedgerEntry::create([
                        'user_id' => $lockedUser->id,
                        'leave_request_id' => $lockedRequest->id,
                        'amount' => $lockedRequest->total_days,
                        'type' => 'refund',
                        'description' => 'Refund for cancelled leave: ' . ucfirst(str_replace('_', ' ', $lockedRequest->leave_type)),
                    ]);
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

        // For managers, their requests can only be approved by Admins (handled by above guards: managers cannot approve their own, and only admin/manager can access this, and managers cannot approve other managers as they aren't assigned to them)

        $applicant = $leaveRequest->user;
        $isDeductible = !in_array($leaveRequest->leave_type, ['unpaid_leave', 'work_from_home']);

        if ($isDeductible && $applicant->leave_balance < $leaveRequest->total_days) {
            return back()->with('error', "Insufficient leave balance. Employee has {$applicant->leave_balance} days available, but requested {$leaveRequest->total_days} days.");
        }

        try {
            DB::transaction(function () use ($leaveRequest, $user, $applicant, $request, $isDeductible) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                if ($lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $lockedRequest->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                    'notes' => $request->input('notes'),
                ]);

                if ($isDeductible) {
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
                        'description' => 'Leave approved: ' . ucfirst(str_replace('_', ' ', $lockedRequest->leave_type)),
                    ]);
                }

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => 'pending',
                    'to_status' => 'approved',
                    'action' => 'approved',
                    'notes' => $request->input('notes') ?? 'Approved by manager/admin.',
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

        // Request must be pending
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
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
                if ($lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $lockedRequest->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                    'rejection_reason' => $request->input('rejection_reason'),
                ]);

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => 'pending',
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

        $newStatus = $request->input('override_status');
        $notes = $request->input('override_notes');

        $applicant = $leaveRequest->user;
        $isDeductible = !in_array($leaveRequest->leave_type, ['unpaid_leave', 'work_from_home']);

        // Check if approval override is valid
        if ($newStatus === 'approved' && $leaveRequest->status !== 'approved' && $isDeductible) {
            if ($applicant->leave_balance < $leaveRequest->total_days) {
                return back()->with('error', "Insufficient leave balance. Employee has {$applicant->leave_balance} days available, but requested {$leaveRequest->total_days} days.");
            }
        }

        try {
            DB::transaction(function () use ($leaveRequest, $user, $applicant, $newStatus, $isDeductible, $notes) {
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->first();
                $oldStatus = $lockedRequest->status;

                $updateData = [
                    'status' => $newStatus,
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                ];

                if ($newStatus === 'approved') {
                    $updateData['notes'] = $notes;
                    $updateData['rejection_reason'] = null;
                } else {
                    $updateData['rejection_reason'] = $notes;
                    $updateData['notes'] = null;
                }

                $lockedRequest->update($updateData);

                if ($isDeductible) {
                    $lockedUser = User::where('id', $applicant->id)->lockForUpdate()->first();

                    // If transitioning to approved from a non-approved state, deduct balance
                    if ($newStatus === 'approved' && $oldStatus !== 'approved') {
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
                            'description' => 'Leave approved via admin override: ' . ucfirst(str_replace('_', ' ', $lockedRequest->leave_type)),
                        ]);
                    }
                    // If transitioning away from approved to rejected, refund balance
                    elseif ($newStatus === 'rejected' && $oldStatus === 'approved') {
                        $lockedUser->leave_balance += $lockedRequest->total_days;
                        $lockedUser->save();

                        LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'leave_request_id' => $lockedRequest->id,
                            'amount' => $lockedRequest->total_days,
                            'type' => 'refund',
                            'description' => 'Refund due to admin override rejection of approved leave',
                        ]);
                    }
                }

                LeaveRequestLog::create([
                    'leave_request_id' => $lockedRequest->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
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
