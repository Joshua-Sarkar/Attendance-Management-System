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
                $resolvedStatus = $att ? $att->status : ($isSunday ? 'weekend' : 'absent');
                return $resolvedStatus === $status;
            });
        }

        $departments = Department::orderBy('name')->get();

        return view('admin.attendance-logs', compact(
            'employees',
            'departments',
            'date',
            'departmentId',
            'search',
            'status'
        ));
    }
}
