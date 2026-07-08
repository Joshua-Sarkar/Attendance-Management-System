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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
            'leave_type' => 'required|string|in:planned,unplanned,complimentary',
            'duration' => 'nullable|string|in:full_day,half_day',
        ];

        $validated = $request->validate($rules);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->startOfDay();
        $isHalfDay = ($validated['duration'] ?? 'full_day') === 'half_day';

        // 2. Duration / Half Day Restrictions
        if ($isHalfDay) {
            if (!$startDate->equalTo($endDate)) {
                return back()->withErrors(['end_date' => 'Half Day Leave must be requested for a single date.'])->withInput();
            }
        }

        try {
            $leaveRequest = \App\Services\LeaveBalanceService::applyRequest($user, [
                'leave_type' => $validated['leave_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_half_day' => $isHalfDay,
                'reason' => $validated['reason'],
            ]);

            $successMsg = $leaveRequest->status === 'approved' 
                ? 'Leave request submitted and automatically approved.'
                : 'Leave request submitted successfully and is pending approval.';

            return redirect()->route('leaves.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            $errorField = 'start_date';
            if ($validated['leave_type'] === 'complimentary') {
                $errorField = 'leave_type';
            } elseif ($validated['leave_type'] === 'unplanned') {
                $errorField = 'end_date';
            }
            return back()->withErrors([$errorField => $e->getMessage()])->withInput();
        }
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
            \App\Services\LeaveBalanceService::cancelRequest($leaveRequest, $user);
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

        try {
            \App\Services\LeaveBalanceService::approveRequest($leaveRequest, $user, $validated['notes'] ?? null);
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
            \App\Services\LeaveBalanceService::rejectRequest($leaveRequest, $user, $request->input('rejection_reason'));
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

        try {
            \App\Services\LeaveBalanceService::overrideRequest(
                $leaveRequest,
                $user,
                $request->input('override_status'),
                $request->input('override_notes')
            );
        } catch (\Exception $e) {
            return redirect()->route('leaves.index')->with('error', 'Override failed: ' . $e->getMessage());
        }

        return redirect()->route('leaves.index')->with('success', 'Leave decision overridden successfully.');
    }
}

