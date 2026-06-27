<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Show employee dashboard with attendance status.
     */
    public function employeeDashboard(): View
    {
        $user = Auth::user();
        $today_attendance = $this->attendanceService->getTodayAttendance($user);
        $recent_history = $this->attendanceService->getAttendanceHistory($user, days: 7);
        $hours_today = $this->attendanceService->calculateTodayHours($user);
        
        // Month's attendance rate (%)
        $startOfMonth = today()->startOfMonth();
        $today = today();
        
        $monthAttendances = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->where('date', '<=', $today)
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));
            
        $monthLeaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today->format('Y-m-d') . ' 23:59:59')
            ->where('end_date', '>=', $startOfMonth->format('Y-m-d') . ' 00:00:00')
            ->get();
            
        $monthPresent = 0;
        $monthAbsent = 0;
        $monthLeave = 0;
        $monthWfh = 0;
        $monthHours = 0.0;
        
        $diffDays = $today->diffInDays($startOfMonth) + 1;
        for ($i = 0; $i < $diffDays; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            if ($date->isSunday()) {
                continue;
            }
            $dateStr = $date->format('Y-m-d');
            
            $dayLeave = $monthLeaves->first(function($leave) use ($date) {
                $dateStr = $date->format('Y-m-d');
                $leaveStartStr = $leave->start_date->format('Y-m-d');
                $leaveEndStr = $leave->end_date->format('Y-m-d');
                return $dateStr >= $leaveStartStr && $dateStr <= $leaveEndStr;
            });
            
            if (isset($monthAttendances[$dateStr])) {
                $record = $monthAttendances[$dateStr];
                if ($record->status === 'present' || $record->status === 'late') {
                    $monthPresent++;
                } elseif ($record->status === 'wfh') {
                    $monthWfh++;
                } elseif ($record->status === 'on_leave') {
                    $monthLeave++;
                } else {
                    $monthAbsent++;
                }
                
                if ($record->check_in_time) {
                    $endTime = $record->check_out_time ?? ($date->isToday() ? now() : null);
                    if ($endTime) {
                        $monthHours += $record->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0;
                    }
                }
            } else {
                if ($dayLeave) {
                    if ($dayLeave->leave_type === 'work_from_home') {
                        $monthWfh++;
                    } else {
                        $monthLeave++;
                    }
                } else {
                    $monthAbsent++;
                }
            }
        }
        
        $totalMonthWorkingDays = $monthPresent + $monthAbsent + $monthLeave + $monthWfh;
        $monthAttendanceRate = $totalMonthWorkingDays > 0
            ? round((($monthPresent + $monthWfh) / $totalMonthWorkingDays) * 100, 1)
            : 100.0;
            
        $now = \Carbon\Carbon::now();
        // Leaves remaining (stored as leave_balance in users table)
        $leavesRemaining = $user->leave_balance;
        
        // Current on-time streak
        $historyDays = 90;
        $streakStartDate = today()->subDays($historyDays);
        
        $allAttendances = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', '>=', $streakStartDate)
            ->where('date', '<=', today())
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));
            
        $allLeaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', today()->format('Y-m-d') . ' 23:59:59')
            ->where('end_date', '>=', $streakStartDate->format('Y-m-d') . ' 00:00:00')
            ->get();
            
        $streak = 0;
        $startTime = config('attendance.start_time', '09:00');
        $graceMinutes = config('attendance.grace_minutes', 15);
        $threshold = today()->setTimeFromTimeString($startTime)->addMinutes($graceMinutes);
        
        $todayIsSunday = today()->isSunday();
        $todayStr = today()->format('Y-m-d');
        $todayHasLeave = $allLeaves->first(function($l) use ($todayStr) {
            return $todayStr >= $l->start_date->format('Y-m-d') && $todayStr <= $l->end_date->format('Y-m-d');
        }) !== null;
        
        $todayHasAttendance = isset($allAttendances[$todayStr]);
        
        $evalDate = today();
        if (!$todayHasAttendance && !$todayIsSunday && !$todayHasLeave) {
            if ($now->lessThanOrEqualTo($threshold)) {
                $evalDate = today()->subDay();
            }
        }
        
        for ($d = 0; $d < $historyDays; $d++) {
            $date = $evalDate->copy()->subDays($d);
            if ($date->isSunday()) {
                continue;
            }
            $dateStr = $date->format('Y-m-d');
            
            $dayLeave = $allLeaves->first(function($l) use ($dateStr) {
                return $dateStr >= $l->start_date->format('Y-m-d') && $dateStr <= $l->end_date->format('Y-m-d');
            });
            
            if ($dayLeave) {
                continue;
            }
            
            if (isset($allAttendances[$dateStr])) {
                $record = $allAttendances[$dateStr];
                if ($record->status === 'present') {
                    $streak++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        
        return view('attendance.employee-dashboard', [
            'user' => $user,
            'today_attendance' => $today_attendance,
            'recent_history' => $recent_history,
            'hours_today' => $hours_today,
            'is_checked_in' => $this->attendanceService->isCheckedInToday($user),
            'is_checked_out' => $this->attendanceService->hasCheckedOutToday($user),
            'month_attendance_rate' => $monthAttendanceRate,
            'leaves_remaining' => $leavesRemaining,
            'on_time_streak' => $streak,
            'month_hours' => $monthHours,
        ]);
    }

    /**
     * Show the authenticated user's own attendance dashboard.
     */
    public function myAttendance(Request $request): View
    {
        $user = $request->user();
        
        // Eager-load relations for profile card
        $user->load(['department', 'manager']);
        
        $today_attendance = $this->attendanceService->getTodayAttendance($user);
        $hours_today = $this->attendanceService->calculateTodayHours($user);
        $is_checked_in = $this->attendanceService->isCheckedInToday($user);
        $is_checked_out = $this->attendanceService->hasCheckedOutToday($user);

        // Fetch stats (last 30 days)
        $stats = $this->attendanceService->getEmployeeStats($user, 30);

        // Fetch 30-day history with exact records and dynamic absence/weekend fallback
        $days = 30;
        $startDate = today()->subDays($days - 1);
        $attendances = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', today())
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));

        $leaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', $startDate)
            ->get();

        $history = [];
        // Loop in reverse chronological order (from today backwards)
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $record = $attendances->get($dateStr);
            
            $hours = null;
            if ($record && $record->check_in_time) {
                $endTime = $record->check_out_time ?? ($date->isToday() ? now() : null);
                if ($endTime) {
                    $hours = $record->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0;
                }
            }

            $dayLeave = $leaves->first(function($leave) use ($date) {
                return $date->between($leave->start_date, $leave->end_date);
            });

            $status = $record ? $record->status : ($date->isSunday() ? 'weekend' : 'absent');
            
            if (!$record && $dayLeave) {
                $status = $dayLeave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave';
            }

            $history[] = [
                'date' => $date,
                'day_of_week' => $date->format('l'),
                'is_weekend' => $date->isSunday(),
                'check_in' => $record?->check_in_time,
                'check_out' => $record?->check_out_time,
                'status' => $status,
                'hours' => $hours,
                'classification' => $record?->classification ?? 'full_day',
                'is_overridden' => $record?->is_overridden ?? false,
            ];
        }

        return view('attendance.my-attendance', compact(
            'user',
            'today_attendance',
            'hours_today',
            'is_checked_in',
            'is_checked_out',
            'stats',
            'history'
        ));
    }

    /**
     * Record check-in for employee.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        try {
            $this->attendanceService->checkIn($user);
            return redirect()->back()->with('success', 'Checked in successfully at ' . now()->format('h:i A'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to check in: ' . $e->getMessage());
        }
    }

    /**
     * Record check-out for employee.
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        try {
            $this->attendanceService->checkOut($user);
            return redirect()->back()->with('success', 'Checked out successfully at ' . now()->format('h:i A'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to check out: ' . $e->getMessage());
        }
    }

    /**
     * Show attendance history for employee.
     */
    public function history(): View
    {
        $user = Auth::user();
        $history = $this->attendanceService->getAttendanceHistory($user, days: 30);
        
        // Calculate monthly stats
        $present_count = $history->filter(fn($att) => in_array($att->status, ['present', 'late']))->count();
        $absent_count = $history->where('status', 'absent')->count();
        $late_count = $history->where('status', 'late')->count();
        
        return view('attendance.history', [
            'user' => $user,
            'history' => $history,
            'present_count' => $present_count,
            'absent_count' => $absent_count,
            'late_count' => $late_count,
        ]);
    }
}
