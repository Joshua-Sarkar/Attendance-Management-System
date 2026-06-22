<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Display the manager dashboard with stats, filters, and employee attendance table.
     */
    public function index(Request $request): mixed
    {
        // Access control: restrict employees to their own dashboard
        if ($request->user()->role === 'employee') {
            return redirect()->route('employee.dashboard');
        }

        $date = $request->input('date', today()->format('Y-m-d'));
        $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;
        $search = $request->input('search');

        // Calculate active workforce metrics if user is admin
        $companyMetrics = null;
        if ($request->user()->role === 'admin') {
            $metricsQuery = \App\Models\User::where('status', 'active');
            if ($departmentId) {
                $metricsQuery->where('department_id', $departmentId);
            }

            $companyMetrics = [
                'people_in_company' => (clone $metricsQuery)->count(),
                'admins' => (clone $metricsQuery)->where('role', 'admin')->count(),
                'managers' => (clone $metricsQuery)->where('role', 'manager')->count(),
                'employees' => (clone $metricsQuery)->where('role', 'employee')->count(),
                'pending_corrections' => \App\Models\ProfileCorrectionRequest::where('status', 'pending')->count(),
            ];
        }

        // Fetch stats and lists for the dashboard
        $stats = $this->attendanceService->getTodayStats($date, $departmentId, $request->user());
        $employees = $this->attendanceService->getFilteredAttendance($date, $departmentId, $search, $request->user());
        $recentActivity = $this->attendanceService->getRecentActivity(10, $request->user());
        $departments = Department::orderBy('name')->get();

        return view('dashboard', compact(
            'stats',
            'employees',
            'recentActivity',
            'departments',
            'date',
            'departmentId',
            'search',
            'companyMetrics'
        ));
    }
}
