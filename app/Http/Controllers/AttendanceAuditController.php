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
                $isSunday = \Carbon\Carbon::parse($date)->isSunday();
                $resolvedStatus = $att ? $att->status : ($isSunday ? 'weekly_off' : 'absent');
                return $resolvedStatus === $status;
            });
        }

        $selectEmployeeId = $request->input('select_employee');
        $departments = Department::orderBy('name')->get();

        // Query overridden attendance records for the audit trail
        $overridesQuery = \App\Models\Attendance::where('is_overridden', true)
            ->with(['user.department', 'overriddenBy']);

        if ($date) {
            $overridesQuery->whereDate('date', $date);
        }
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
            
            return [
                'timestamp' => $first->overridden_at,
                'administrator' => $first->overriddenBy?->name ?? 'System',
                'action' => 'Override status to ' . ucwords(str_replace('_', ' ', $first->status)) . ($first->classification ? ' (' . ($first->classification === 'half_day' ? 'Half Day' : 'Full Day') . ')' : ''),
                'scope' => $scope,
                'affected_count' => $count,
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
