<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use App\Services\AttendanceStateRegistry;
use Illuminate\Http\Request;

class AttendanceAuditController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Display the Attendance Logs view for admins.
     */
    public function index(Request $request): mixed
    {
        // Restrict to admins only
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $date = $request->input('date', today()->format('Y-m-d'));
        $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;
        $search = $request->input('search');
        $status = $request->input('status');

        // Fetch all employees matching search/department filters for the specified date
        $employees = $this->attendanceService->getFilteredAttendance($date, $departmentId, $search, $request->user());

        $parsedDate = \Carbon\Carbon::parse($date);

        // N+1 Query Prevention: Eager-load leave requests for all fetched employees on the selected date
        $employeeIds = $employees->pluck('id')->toArray();
        $dateStr = $parsedDate->format('Y-m-d');
        $leaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('start_date', '<=', $dateStr . ' 23:59:59')
            ->where('end_date', '>=', $dateStr . ' 00:00:00')
            ->get()
            ->groupBy('user_id');

        // Map resolved state to each employee using the canonical state resolver
        foreach ($employees as $emp) {
            $empLeave = isset($leaves[$emp->id]) ? $leaves[$emp->id]->first() : null;
            $emp->resolved_state = $this->attendanceService->resolveStateForDate($emp, $parsedDate, $emp->today_attendance, $empLeave);
        }

        // Filter by status in-memory using the canonical registry mapping
        if ($status) {
            $employees = $employees->filter(function ($emp) use ($status) {
                $resolvedStatus = $emp->resolved_state['status'];
                if ($status === 'late') {
                    if ($resolvedStatus === 'late') {
                        return true;
                    }
                    if ($resolvedStatus === 'half') {
                        $checkInTime = $emp->resolved_state['check_in_time'];
                        $timings = $emp->resolved_state['timings'];
                        if ($checkInTime && $timings && $timings['grace_threshold']) {
                            $checkInMin = \Carbon\Carbon::parse($checkInTime)->second(0)->microsecond(0);
                            $graceThreshold = \Carbon\Carbon::parse($timings['grace_threshold'])->second(0)->microsecond(0);
                            return $checkInMin->gt($graceThreshold);
                        }
                    }
                    return false;
                }
                if ($status === 'present') {
                    return in_array($resolvedStatus, ['present', 'late', 'half']);
                }
                return AttendanceStateRegistry::getDisplayStatus($resolvedStatus) === $status;
            });
        }

        $selectEmployeeId = $request->input('select_employee');
        $departments = Department::orderBy('name')->get();

        // Query overridden attendance records for the audit trail
        $overridesQuery = \App\Models\Attendance::where('is_overridden', true)
            ->with(['user.department', 'overriddenBy']);

        if ($departmentId) {
            $overridesQuery->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        if ($search) {
            $overridesQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('employee_id', 'like', '%' . $search . '%');
            });
        }
        if ($status) {
            if ($status === 'present') {
                $overridesQuery->whereIn('status', ['present', 'late']);
            } else {
                $overridesQuery->where('status', $status);
            }
        }

        $overrides = $overridesQuery->orderByDesc('overridden_at')->get();

        // Group overrides to build action-oriented timeline logs
        $groupedOverrides = $overrides->groupBy(function ($item) {
            $timeStr = $item->overridden_at ? $item->overridden_at->format('Y-m-d H:i:s') : '—';
            return $timeStr . '_' . $item->overridden_by . '_' . md5($item->override_reason);
        })->map(function ($group) {
            $first = $group->first();
            $count = $group->count();
            
            // Resolve Scope details
            $scope = 'Individual';
            if ($first->override_type === 'bulk') {
                $deptIds = $group->pluck('user.department_id')->unique()->filter();
                if ($deptIds->count() === 1) {
                    $scope = 'Department';
                } else {
                    $scope = 'Multiple Employees';
                }
            }
            
            // Extract metadata if present
            $meta = $first->metadata;
            $conflictStrategy = $meta['conflict_strategy'] ?? 'replace';
            
            if (!empty($meta['dates_affected'])) {
                $datesAffected = implode(', ', $meta['dates_affected']);
            } else {
                $datesAffected = $group->pluck('date')
                    ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                    ->unique()
                    ->sort()
                    ->implode(', ');
            }
            
            $employeesCount = !empty($meta['employees_count']) ? $meta['employees_count'] : $group->pluck('user_id')->unique()->count();
            $recordsModified = !empty($meta['records_modified']) ? $meta['records_modified'] : $count;

            $statusLabel = AttendanceStateRegistry::getLabel($first->status);

            return [
                'timestamp' => $first->overridden_at,
                'administrator' => $first->overriddenBy?->name ?? 'System',
                'action' => 'Override status to ' . $statusLabel . ($first->classification ? ' (' . ($first->classification === 'half_day' ? 'Half Day' : 'Full Day') . ')' : ''),
                'scope' => $scope,
                'affected_count' => $employeesCount,
                'records_modified' => $recordsModified,
                'dates_affected' => $datesAffected,
                'conflict_strategy' => $conflictStrategy,
                'reason' => $first->override_reason,
                'items' => $group,
            ];
        })->values();

        return view('admin.attendance-logs', compact(
            'employees',
            'departments',
            'date',
            'departmentId',
            'search',
            'status',
            'groupedOverrides',
            'selectEmployeeId'
        ));
    }
}
