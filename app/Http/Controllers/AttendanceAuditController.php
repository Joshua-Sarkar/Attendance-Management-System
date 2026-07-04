<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\AttendanceService;
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

        // Filter by status in-memory because of dynamic status logic (absent status, leaves, wfh etc.)
        if ($status) {
            $employees = $employees->filter(function ($emp) use ($status, $date) {
                $att = $emp->today_attendance;
                $parsedDate = \Carbon\Carbon::parse($date);
                $isWeeklyOff = \App\Services\AttendanceTimingResolver::isWeeklyOff($parsedDate);
                $resolvedStatus = $att ? $att->status : ($isWeeklyOff ? 'weekly_off' : 'absent');
                return $resolvedStatus === $status;
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
            $overridesQuery->where('status', $status);
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

            $statusLabel = $first->status;
            if ($statusLabel === 'paid_leave') {
                $statusLabel = 'Planned Leave (Paid)';
            } elseif ($statusLabel === 'unpaid_leave') {
                $statusLabel = 'Unplanned Leave (Unpaid)';
            } else {
                $statusLabel = ucwords(str_replace('_', ' ', $statusLabel));
            }

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
