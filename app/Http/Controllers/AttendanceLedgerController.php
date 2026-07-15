<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\PayrollAuditLog;
use App\Services\AttendanceService;
use App\Services\AttendanceTimingResolver;
use App\Services\AttendanceStateRegistry;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceLedgerController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Display the Workforce Attendance Ledger matrix.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            abort(403, 'Unauthorized access to Workforce Attendance Ledger.');
        }

        // Determine date period parameters
        $monthParam = $request->input('month', '2026-07');
        $carbonMonth = Carbon::parse($monthParam . '-01');
        $activeRange = $request->input('range', 'month');

        $startDate = $carbonMonth->copy()->startOfMonth();
        $endDate = $carbonMonth->copy()->endOfMonth();

        if ($activeRange === 'today') {
            // Anchor to July 4, 2026 if looking at July 2026, else current date
            $refDate = today();
            if ($refDate->format('Y-m') !== $carbonMonth->format('Y-m')) {
                $refDate = $carbonMonth->copy()->day(4);
            }
            $startDate = $refDate->copy()->startOfDay();
            $endDate = $refDate->copy()->startOfDay();
        } elseif ($activeRange === 'week') {
            $refDate = today();
            if ($refDate->format('Y-m') !== $carbonMonth->format('Y-m')) {
                $refDate = $carbonMonth->copy()->day(4);
            }
            $endDate = $refDate->copy()->startOfDay();
            $startDate = $endDate->copy()->subDays(6)->startOfDay();
        } elseif ($activeRange === 'custom') {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : $carbonMonth->copy()->startOfMonth();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->startOfDay() : $carbonMonth->copy()->endOfMonth();
        }

        // 1. Get filtered active employees list
        $employeesQuery = User::where('status', 'active')
            ->where('role', 'employee')
            ->with(['department', 'manager', 'employeeProfile']);

        if ($user->role === 'manager') {
            $employeesQuery->where('manager_id', $user->id);
        }

        if ($request->input('department_id')) {
            $employeesQuery->where('department_id', $request->input('department_id'));
        }

        if ($request->input('shift')) {
            $employeesQuery->whereHas('employeeProfile', function ($q) use ($request) {
                $q->where('shift', $request->input('shift'));
            });
        }

        if ($request->input('location')) {
            $employeesQuery->whereHas('employeeProfile', function ($q) use ($request) {
                $q->where('location', $request->input('location'));
            });
        }

        if ($request->input('search')) {
            $search = $request->input('search');
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $employeesQuery->orderBy('name')->get();
        $employeeIds = $employees->pluck('id')->toArray();

        // 2. Fetch preloaded Attendance and Leaves to prevent N+1 queries
        $attendances = Attendance::whereIn('user_id', $employeeIds)
            ->where('date', '>=', $startDate->format('Y-m-d'))
            ->where('date', '<=', $endDate->format('Y-m-d'))
            ->get()
            ->groupBy('user_id');

        $leaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('start_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->where('end_date', '>=', $startDate->format('Y-m-d 00:00:00'))
            ->get()
            ->groupBy('user_id');

        // Resolve calendar dates list
        $dateList = [];
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            $dateList[] = $tempDate->copy();
            $tempDate->addDay();
        }

        // Build the resolved matrix dataset
        $matrix = [];
        $totalPresent = 0;
        $totalLate = 0;
        $totalWfh = 0;
        $totalLeave = 0;
        $totalAbsent = 0;
        $totalHours = 0.0;
        $totalHoursCount = 0;
        $totalOvertimeHours = 0.0;
        $totalPaidLeave = 0;
        $totalUnplannedLeave = 0;
        $totalBirthday = 0;
        $totalHalfDay = 0;

        $dailyTrend = [];
        foreach ($dateList as $d) {
            $dailyTrend[$d->format('Y-m-d')] = [
                'day' => $d->day,
                'present' => 0,
                'late' => 0,
                'wfh' => 0,
                'absent' => 0,
                'leave' => 0,
                'weekoff' => 0,
                'hours_sum' => 0.0,
                'hours_count' => 0,
                'ot_sum' => 0.0,
            ];
        }

        $byDept = [];
        $byShift = [];
        $byLoc = [];
        $byManager = [];

        foreach ($employees as $emp) {
            $empAttendances = $attendances->get($emp->id, collect())->keyBy(fn($att) => Carbon::parse($att->date)->format('Y-m-d'));
            $empLeaves = $leaves->get($emp->id, collect());
            
            $empDept = $emp->department->name ?? 'Unassigned';
            $empShift = $emp->employeeProfile->shift ?? 'Regular Shift';
            $empLoc = $emp->employeeProfile->location ?? 'HQ, Dehradun';
            $empMgr = $emp->manager->name ?? 'None';

            if (!isset($byDept[$empDept])) {
                $byDept[$empDept] = ['present' => 0, 'late' => 0, 'wfh' => 0, 'absent' => 0, 'leave' => 0, 'total' => 0, 'hours_sum' => 0.0, 'hours_count' => 0, 'ot_sum' => 0.0, 'headcount' => 0];
            }
            $byDept[$empDept]['headcount']++;

            if (!isset($byManager[$empMgr])) {
                $byManager[$empMgr] = ['present' => 0, 'late' => 0, 'wfh' => 0, 'absent' => 0, 'leave' => 0, 'total' => 0, 'headcount' => 0, 'pending' => 0];
            }
            $byManager[$empMgr]['headcount']++;

            $byShift[$empShift] = ($byShift[$empShift] ?? 0) + 1;
            $byLoc[$empLoc] = ($byLoc[$empLoc] ?? 0) + 1;

            $empMatrix = [];
            foreach ($dateList as $d) {
                $dStr = $d->format('Y-m-d');
                $record = $empAttendances->get($dStr);
                
                $dayLeave = $empLeaves->first(function ($l) use ($d) {
                    return $d->between(Carbon::parse($l->start_date)->startOfDay(), Carbon::parse($l->end_date)->startOfDay());
                });

                $resolved = $this->attendanceService->resolveStateForDate($emp, $d, $record, $dayLeave);
                $empMatrix[$dStr] = $resolved;

                $status = $resolved['status'];
                $hours = (float) $resolved['hours'];

                $isWorkable = !in_array($status, ['off', 'future']);
                if ($isWorkable) {
                    $byDept[$empDept]['total']++;
                    $byManager[$empMgr]['total']++;
                }

                $ot = 0.0;
                if ($resolved['check_in_time'] && $resolved['check_out_time']) {
                    $ot = max(0.0, $hours - 8.0);
                }

                if (in_array($status, ['present', 'wfh', 'late'])) {
                    $totalPresent++;
                    if ($status === 'wfh') $totalWfh++;
                    if ($status === 'late') $totalLate++;

                    $totalHours += $hours;
                    $totalHoursCount++;
                    $totalOvertimeHours += $ot;

                    $dailyTrend[$dStr][$status === 'wfh' ? 'wfh' : ($status === 'late' ? 'late' : 'present')]++;
                    $dailyTrend[$dStr]['hours_sum'] += $hours;
                    $dailyTrend[$dStr]['hours_count']++;
                    $dailyTrend[$dStr]['ot_sum'] += $ot;

                    $byDept[$empDept][$status === 'wfh' ? 'wfh' : ($status === 'late' ? 'late' : 'present')]++;
                    $byDept[$empDept]['hours_sum'] += $hours;
                    $byDept[$empDept]['hours_count']++;
                    $byDept[$empDept]['ot_sum'] += $ot;

                    $byManager[$empMgr][$status === 'wfh' ? 'wfh' : ($status === 'late' ? 'late' : 'present')]++;
                } elseif (in_array($status, ['half', 'hd_upr', 'hd_upa', 'hdp'])) {
                    $totalHalfDay++;
                    $totalHours += $hours;
                    $totalHoursCount++;

                    $dailyTrend[$dStr]['present']++; 
                    $dailyTrend[$dStr]['hours_sum'] += $hours;
                    $dailyTrend[$dStr]['hours_count']++;

                    $byDept[$empDept]['present']++;
                    $byDept[$empDept]['hours_sum'] += $hours;
                    $byDept[$empDept]['hours_count']++;

                    $byManager[$empMgr]['present']++;

                    if (in_array($status, ['hdp', 'hd_upa'])) {
                        $totalLeave += 0.5;
                        if ($status === 'hd_upa') $totalUnplannedLeave += 0.5;
                        else $totalPaidLeave += 0.5;
                    } else {
                        $totalAbsent += 0.5;
                    }
                } elseif (in_array($status, ['planned', 'upa', 'bday'])) {
                    $totalLeave++;
                    if ($status === 'upa') $totalUnplannedLeave++;
                    elseif ($status === 'bday') $totalBirthday++;
                    else $totalPaidLeave++;

                    $dailyTrend[$dStr]['leave']++;
                    $byDept[$empDept]['leave']++;
                    $byManager[$empMgr]['leave']++;
                } elseif ($status === 'absent' || $status === 'upr') {
                    $totalAbsent++;
                    $dailyTrend[$dStr]['absent']++;
                    $byDept[$empDept]['absent']++;
                    $byManager[$empMgr]['absent']++;
                } elseif ($status === 'off') {
                    $dailyTrend[$dStr]['weekoff']++;
                }
            }

            $matrix[$emp->id] = [
                'employee' => $emp,
                'dates' => $empMatrix,
            ];
        }

        // Metrics calculations
        $healthTotal = $totalPresent + $totalLeave + $totalAbsent;
        $attendanceRate = $healthTotal > 0 ? round(($totalPresent / $healthTotal) * 100, 1) : 0;
        $absenteeismRate = $healthTotal > 0 ? round(($totalAbsent / $healthTotal) * 100, 1) : 0;
        $avgHoursPerDay = $totalHoursCount > 0 ? round($totalHours / $totalHoursCount, 1) : 0.0;

        // Pending approvals from real DB
        $pendingCorrections = \App\Models\ProfileCorrectionRequest::where('status', 'pending')->count();
        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $totalPending = $pendingCorrections + $pendingLeaves;

        $totalPayrollCost = 0.0;
        if ($user->role === 'admin') {
            $totalPayrollCost = PayrollRecord::whereIn('user_id', $employeeIds)
                ->whereHas('payrollCycle', function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                      ->where('end_date', '>=', $startDate->format('Y-m-d'));
                })
                ->sum('net_salary') ?? 0.0;
        }

        $metrics = [
            'headcount' => $employees->count(),
            'attendance_rate' => $attendanceRate,
            'avg_hours' => $avgHoursPerDay,
            'overtime' => $totalOvertimeHours,
            'absenteeism_rate' => $absenteeismRate,
            'pending' => $totalPending,
            'payroll_cost' => $totalPayrollCost,
        ];

        // Format shifts, locations, departments list for filters
        $departments = Department::orderBy('name')->get();
        $allActiveShifts = User::where('status', 'active')
            ->whereHas('employeeProfile', function ($q) {
                $q->whereNotNull('shift');
            })
            ->get()
            ->map(fn($e) => $e->employeeProfile->shift)
            ->unique()
            ->values();

        $allLocations = User::where('status', 'active')
            ->whereHas('employeeProfile', function ($q) {
                $q->whereNotNull('location');
            })
            ->get()
            ->map(fn($e) => $e->employeeProfile->location)
            ->unique()
            ->values();

        return view('admin.attendance.ledger.index', compact(
            'matrix',
            'dateList',
            'metrics',
            'dailyTrend',
            'byDept',
            'byShift',
            'byLoc',
            'byManager',
            'departments',
            'allActiveShifts',
            'allLocations',
            'carbonMonth',
            'activeRange',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show detailed day dossier context for an employee and date.
     */
    public function dossier(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $employee = User::with(['department', 'manager', 'employeeProfile'])->findOrFail($request->employee_id);
        if ($user->role === 'manager' && $employee->manager_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this employee.'], 403);
        }

        $date = Carbon::parse($request->date)->startOfDay();
        $dateStr = $date->format('Y-m-d');

        $record = Attendance::where('user_id', $employee->id)->where('date', $date)->first();
        
        $leave = LeaveRequest::where('user_id', $employee->id)
            ->where('start_date', '<=', $dateStr . ' 23:59:59')
            ->where('end_date', '>=', $dateStr . ' 00:00:00')
            ->first();

        $resolved = $this->attendanceService->resolveStateForDate($employee, $date, $record, $leave);

        // Calculate exact late minutes and overtime from canonical start/end times
        $lateMinutes = 0;
        if ($resolved['check_in_time'] && $resolved['shift_start']) {
            $ci = Carbon::parse($resolved['check_in_time']);
            $ss = Carbon::parse($resolved['shift_start']);
            if ($ci->greaterThan($ss)) {
                $lateMinutes = (int) $ss->diffInMinutes($ci, true);
            }
        }

        $overtime = 0.0;
        if ($resolved['check_in_time'] && $resolved['check_out_time'] && $resolved['hours'] > 8) {
            $overtime = round($resolved['hours'] - 8, 1);
        }

        $resolved['late_minutes'] = $lateMinutes;
        $resolved['overtime'] = $overtime;

        $leaveContext = null;
        if ($leave) {
            $leaveContext = [
                'has_leave' => true,
                'leave_id' => $leave->id,
                'leave_type' => $leave->leave_type,
                'leave_type_label' => $leave->leave_type_label,
                'status' => $leave->status,
                'is_half_day' => (bool)$leave->is_half_day,
                'reason' => $leave->reason,
                'notes' => $leave->notes,
                'affected_attendance' => in_array($resolved['status'], ['planned', 'upa', 'bday', 'half', 'hdp', 'hd_upa', 'hd_upr'])
            ];
        }

        // Fetch payroll impact if unlocked payroll record exists
        $payroll = PayrollRecord::where('user_id', $employee->id)
            ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr);
            })
            ->first();

        $payrollImpact = null;
        if ($payroll) {
            $meta = $payroll->calculation_metadata ?? [];
            $dayData = $meta['daily_breakdown'][$dateStr] ?? null;

            $payrollImpact = [
                'period' => $payroll->payrollCycle->period,
                'locked' => (bool) $payroll->locked,
                'calculation_version' => $payroll->calculation_version,
                'base_salary' => $payroll->base_salary,
                'daily_rate' => $meta['daily_rate'] ?? 0.0,
                'deduction_factor' => $dayData['deduction_factor'] ?? 0.0,
                'deducted_amount' => $dayData['deducted_amount'] ?? 0.0,
                'employee_approval' => $payroll->employee_review_status,
                'admin_approval' => $payroll->admin_approved_at ? 'Approved' : 'Pending',
            ];
        }

        // Get override history/audit details
        $history = [];
        if ($record && $record->is_overridden) {
            $history = [
                'status' => $record->status,
                'original_status' => $record->automatic_status ?? $record->status,
                'changed_by' => $record->overriddenBy?->name ?? 'Administrator',
                'reason' => $record->override_reason,
                'timestamp' => $record->overridden_at ? $record->overridden_at->format('d M Y, h:i A') : '—',
            ];
        }

        // Build audit timeline
        $audit = [];
        if ($record && $record->is_overridden) {
            $audit[] = [
                'action' => 'Attendance status overridden to: ' . strtoupper($record->status) . ' (' . ($record->classification === 'half_day' ? 'Half Day' : 'Full Day') . ')',
                'performed_by' => $record->overriddenBy?->name ?? 'Administrator',
                'reason' => $record->override_reason,
                'timestamp' => $record->overridden_at ? $record->overridden_at->format('d M Y, h:i A') : '—',
            ];
        }
        if ($leave) {
            $leaveLogs = \App\Models\LeaveRequestLog::where('leave_request_id', $leave->id)
                ->with('user')
                ->get();
            foreach ($leaveLogs as $log) {
                $audit[] = [
                    'action' => 'Leave request ' . $log->action . ' (Type: ' . $leave->leave_type_label . ')',
                    'performed_by' => $log->user?->name ?? 'System',
                    'reason' => $log->notes,
                    'timestamp' => $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—',
                ];
            }
        }
        if ($payroll) {
            $payrollLogs = \App\Models\PayrollAuditLog::where('user_id', $employee->id)
                ->with('actor')
                ->get();
            foreach ($payrollLogs as $log) {
                $audit[] = [
                    'action' => $log->action . ' (' . $log->category . ')',
                    'performed_by' => $log->actor?->name ?? 'System',
                    'reason' => $log->reason,
                    'timestamp' => $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—',
                ];
            }
        }

        // Sort audit by timestamp descending
        usort($audit, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department->name ?? 'Unassigned',
                'designation' => $employee->employeeProfile->designation ?? 'Employee',
                'manager' => $employee->manager->name ?? 'None',
            ],
            'date_context' => [
                'date' => $dateStr,
                'formatted' => $date->format('d F Y'),
                'day_of_week' => $date->format('l'),
                'is_working_day' => !AttendanceTimingResolver::isWeeklyOff($date),
                'is_future' => $date->greaterThan(today()),
            ],
            'resolved' => $resolved,
            'leave_context' => $leaveContext,
            'payroll_impact' => $payrollImpact,
            'history' => $history,
            'audit' => $audit,
        ]);
    }

    /**
     * Submit an individual attendance correction/override.
     */
    public function override(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|string',
            'classification' => 'nullable|string|in:automatic,full_day,half_day',
            'override_reason' => 'required|string|min:5',
        ]);

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $employee = User::findOrFail($request->employee_id);
        if ($user->role === 'manager' && $employee->manager_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this employee.'], 403);
        }

        $dateStr = Carbon::parse($request->date)->format('Y-m-d');

        // Check if payroll is locked for this date
        $payrollRecord = PayrollRecord::where('user_id', $employee->id)
            ->where('locked', true)
            ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr);
            })
            ->first();

        if ($payrollRecord) {
            return response()->json(['error' => 'Cannot modify attendance. Payroll is locked and immutable for this period.'], 422);
        }

        $params = [
            'scope_type' => 'employee',
            'employee_ids' => [$employee->id],
            'date_mode' => 'single',
            'date' => $dateStr,
            'status' => $request->status,
            'classification' => $request->classification ?? 'automatic',
            'override_reason' => $request->override_reason,
            'conflict_handling' => 'replace',
        ];

        try {
            $result = $this->attendanceService->applyBulkOverride($params, $user);

            $refreshedRecord = Attendance::where('user_id', $employee->id)->where('date', $dateStr)->first();
            $leave = LeaveRequest::where('user_id', $employee->id)
                ->where('start_date', '<=', $dateStr . ' 23:59:59')
                ->where('end_date', '>=', $dateStr . ' 00:00:00')
                ->first();
            $newResolved = $this->attendanceService->resolveStateForDate($employee, Carbon::parse($dateStr), $refreshedRecord, $leave);

            $payroll = PayrollRecord::where('user_id', $employee->id)
                ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                    $q->where('start_date', '<=', $dateStr)
                      ->where('end_date', '>=', $dateStr);
                })
                ->first();

            $payrollImpact = null;
            if ($payroll) {
                $meta = $payroll->calculation_metadata ?? [];
                $dayData = $meta['daily_breakdown'][$dateStr] ?? null;

                $payrollImpact = [
                    'period' => $payroll->payrollCycle->period,
                    'locked' => (bool) $payroll->locked,
                    'calculation_version' => $payroll->calculation_version,
                    'base_salary' => $payroll->base_salary,
                    'daily_rate' => $meta['daily_rate'] ?? 0.0,
                    'deduction_factor' => $dayData['deduction_factor'] ?? 0.0,
                    'deducted_amount' => $dayData['deducted_amount'] ?? 0.0,
                    'employee_approval' => $payroll->employee_review_status,
                    'admin_approval' => $payroll->admin_approved_at ? 'Approved' : 'Pending',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance override applied successfully.',
                'applied_count' => $result['applied_count'],
                'resolved' => $newResolved,
                'payroll_impact' => $payrollImpact,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Preview bulk override conflicts and affected counts.
     */
    public function bulkPreview(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
            'date_mode' => 'required|string|in:single,range,multiple',
            'date' => 'required_if:date_mode,single|nullable|date',
            'start_date' => 'required_if:date_mode,range|nullable|date',
            'end_date' => 'required_if:date_mode,range|nullable|date',
            'dates' => 'required_if:date_mode,multiple|array',
            'dates.*' => 'date',
            'status' => 'required|string',
            'classification' => 'nullable|string|in:automatic,full_day,half_day',
            'override_reason' => 'required|string|min:5',
            'conflict_handling' => 'required|string|in:skip,replace,cancel',
        ]);

        // If manager, verify all selected employees belong to them
        if ($user->role === 'manager') {
            $unauthorized = User::whereIn('id', $validated['employee_ids'])
                ->where('manager_id', '!=', $user->id)
                ->exists();
            if ($unauthorized) {
                return response()->json(['error' => 'Unauthorized. Some selected employees are not in your team.'], 403);
            }
        }

        $params = array_merge($validated, [
            'scope_type' => 'employee',
        ]);

        try {
            // Count expected affected payroll records
            $dates = $this->attendanceService->resolveBulkOverrideDates($params);
            $userIds = $validated['employee_ids'];
            $lockedCount = 0;

            foreach ($userIds as $uid) {
                foreach ($dates as $d) {
                    $dStr = $d->format('Y-m-d');
                    $isLocked = PayrollRecord::where('user_id', $uid)
                        ->where('locked', true)
                        ->whereHas('payrollCycle', function ($q) use ($dStr) {
                            $q->where('start_date', '<=', $dStr)
                              ->where('end_date', '>=', $dStr);
                        })
                        ->exists();
                    if ($isLocked) {
                        $lockedCount++;
                    }
                }
            }

            $preview = $this->attendanceService->getBulkOverridePreview($params, $user);
            $preview['locked_payroll_conflicts'] = $lockedCount;
            if ($lockedCount > 0) {
                $preview['has_conflicts'] = true;
                $preview['conflict_message'] = ($preview['conflict_message'] ? $preview['conflict_message'] . ' ' : '') . 
                    "Error: {$lockedCount} conflict(s) with Locked Payroll detected. These records are immutable.";
            }

            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Submit a bulk attendance correction/override.
     */
    public function bulkOverride(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
            'date_mode' => 'required|string|in:single,range,multiple',
            'date' => 'required_if:date_mode,single|nullable|date',
            'start_date' => 'required_if:date_mode,range|nullable|date',
            'end_date' => 'required_if:date_mode,range|nullable|date',
            'dates' => 'required_if:date_mode,multiple|array',
            'dates.*' => 'date',
            'status' => 'required|string',
            'classification' => 'nullable|string|in:automatic,full_day,half_day',
            'override_reason' => 'required|string|min:5',
            'conflict_handling' => 'required|string|in:skip,replace,cancel',
        ]);

        // If manager, verify all selected employees belong to them
        if ($user->role === 'manager') {
            $unauthorized = User::whereIn('id', $validated['employee_ids'])
                ->where('manager_id', '!=', $user->id)
                ->exists();
            if ($unauthorized) {
                return response()->json(['error' => 'Unauthorized. Some selected employees are not in your team.'], 403);
            }
        }

        // Verify no locked payroll overlaps
        $dates = $this->attendanceService->resolveBulkOverrideDates($validated);
        $userIds = $validated['employee_ids'];

        foreach ($userIds as $uid) {
            foreach ($dates as $d) {
                $dStr = $d->format('Y-m-d');
                $isLocked = PayrollRecord::where('user_id', $uid)
                    ->where('locked', true)
                    ->whereHas('payrollCycle', function ($q) use ($dStr) {
                        $q->where('start_date', '<=', $dStr)
                          ->where('end_date', '>=', $dStr);
                    })
                    ->exists();
                if ($isLocked) {
                    return response()->json(['error' => "Cannot apply bulk override. Payroll for employee ID {$uid} is locked and immutable on {$dStr}."], 422);
                }
            }
        }

        $params = array_merge($validated, [
            'scope_type' => 'employee',
        ]);

        try {
            $result = $this->attendanceService->applyBulkOverride($params, $user);
            return response()->json([
                'success' => true,
                'message' => 'Bulk attendance override applied successfully.',
                'applied_count' => $result['applied_count'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Assign Leave to an employee for a specific date.
     */
    public function assignLeave(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'leave_type' => 'required|string|in:planned,unplanned,complimentary',
            'duration' => 'required|string|in:full_day,half_day',
            'reason' => 'required|string|min:5',
        ]);

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $employee = User::findOrFail($request->employee_id);
        if ($user->role === 'manager' && $employee->manager_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this employee.'], 403);
        }

        $dateStr = Carbon::parse($request->date)->format('Y-m-d');

        // Check if payroll is locked for this date
        $isLocked = PayrollRecord::where('user_id', $employee->id)
            ->where('locked', true)
            ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr);
            })
            ->exists();

        if ($isLocked) {
            return response()->json(['error' => 'Cannot assign leave. Payroll is locked and immutable for this period.'], 422);
        }

        try {
            DB::transaction(function () use ($employee, $request, $dateStr, $user) {
                $leaveRequest = \App\Services\LeaveBalanceService::applyRequest($employee, [
                    'leave_type' => $request->leave_type,
                    'start_date' => $dateStr,
                    'end_date' => $dateStr,
                    'is_half_day' => $request->duration === 'half_day',
                    'reason' => $request->reason,
                ]);

                if ($leaveRequest->status !== 'approved') {
                    \App\Services\LeaveBalanceService::approveRequest($leaveRequest, $user, 'Assigned by admin/manager from Attendance Ledger.');
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave assigned and approved successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Change Shift for an employee on a specific date.
     */
    public function changeShift(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'shift_start_time' => 'required|string',
            'shift_end_time' => 'required|string',
            'grace_minutes' => 'required|integer|min:0',
        ]);

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $employee = User::findOrFail($request->employee_id);
        if ($user->role === 'manager' && $employee->manager_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this employee.'], 403);
        }

        $dateStr = Carbon::parse($request->date)->format('Y-m-d');

        // Check if payroll is locked for this date
        $isLocked = PayrollRecord::where('user_id', $employee->id)
            ->where('locked', true)
            ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr);
            })
            ->exists();

        if ($isLocked) {
            return response()->json(['error' => 'Cannot modify shift. Payroll is locked and immutable for this period.'], 422);
        }

        try {
            DB::transaction(function () use ($employee, $request, $dateStr, $user) {
                $attendance = Attendance::firstOrCreate(
                    ['user_id' => $employee->id, 'date' => Carbon::parse($dateStr)->startOfDay()],
                    ['status' => 'absent', 'classification' => 'full_day']
                );

                $metadata = $attendance->metadata ?? [];
                $metadata['shift_start_time'] = $request->shift_start_time;
                $metadata['shift_end_time'] = $request->shift_end_time;
                $metadata['grace_minutes'] = (int) $request->grace_minutes;
                $attendance->metadata = $metadata;
                $attendance->save();

                // Recompute automatic classification and resolve state
                if ($attendance->check_in_time) {
                    $timings = AttendanceTimingResolver::resolveTimings($employee, Carbon::parse($dateStr));
                    $threshold = $timings['grace_threshold'];

                    $nowMin = Carbon::parse($attendance->check_in_time)->second(0)->microsecond(0);
                    $thresholdMin = $threshold->copy()->second(0)->microsecond(0);

                    $isLateHalfDay = $nowMin->greaterThan($thresholdMin);

                    $hours = 0.0;
                    if ($attendance->check_out_time) {
                        $hours = Carbon::parse($attendance->check_in_time)->diffInMinutes(Carbon::parse($attendance->check_out_time), true) / 60.0;
                    }
                    $isHoursHalfDay = $attendance->check_out_time && $hours < 4.0;

                    if ($isLateHalfDay || $isHoursHalfDay) {
                        $attendance->automatic_classification = 'half_day';
                        $attendance->automatic_classification_reason = $isHoursHalfDay ? 'insufficient_hours' : 'late_arrival';
                        if (!$attendance->is_overridden) {
                            $attendance->classification = 'half_day';
                            $attendance->status = 'half';
                        }
                    } else {
                        $attendance->automatic_classification = 'full_day';
                        $attendance->automatic_classification_reason = null;
                        if (!$attendance->is_overridden) {
                            $attendance->classification = 'full_day';
                            $attendance->status = 'present';
                        }
                    }
                    $attendance->save();
                }

                // Dispatch event to recalculate payroll
                event(new \App\Events\AttendanceOverridden($employee, Carbon::parse($dateStr), $user));
            });

            return response()->json([
                'success' => true,
                'message' => 'Shift updated and attendance recomputed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
