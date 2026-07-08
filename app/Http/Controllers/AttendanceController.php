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
        
        $states = $this->attendanceService->getAttendanceStatesForRange($user, $startOfMonth, $today);
            
        $monthPresent = 0;
        $monthAbsent = 0;
        $monthLeave = 0;
        $monthWfh = 0;
        $monthHours = 0.0;
        
        foreach ($states as $state) {
            $status = $state['status'];
            if ($status === 'off' || $status === 'future') {
                continue;
            }
            
            if ($status === 'present' || $status === 'late') {
                $monthPresent++;
            } elseif ($status === 'half') {
                $monthPresent++;
            } elseif ($status === 'absent' || $status === 'upr' || $status === 'hd_upr') {
                $monthAbsent++;
            } elseif (in_array($status, ['planned', 'upa', 'hdp', 'hd_upa', 'bday'])) {
                $monthLeave++;
            }
            
            $monthHours += (float) $state['hours'];
        }

        $totalMonthWorkingDays = $monthPresent + $monthAbsent + $monthLeave + $monthWfh;
        $monthAttendanceRate = $totalMonthWorkingDays > 0
            ? round((($monthPresent + $monthWfh) / $totalMonthWorkingDays) * 100, 1)
            : 100.0;
            
        $now = \Carbon\Carbon::now();
        $leavesRemaining = $user->leave_balance;
        
        // Current on-time streak
        $historyDays = 90;
        $streakStartDate = today()->subDays($historyDays);
        
        $historyStates = $this->attendanceService->getAttendanceStatesForRange($user, $streakStartDate, today());
        
        $timings = \App\Services\AttendanceTimingResolver::resolveTimings($user, today());
        $threshold = $timings['grace_threshold'];
        
        $todayIsWeeklyOff = \App\Services\AttendanceTimingResolver::isWeeklyOff(today());
        $todayStr = today()->format('Y-m-d');
        $todayState = $historyStates[$todayStr] ?? null;
        $todayHasLeave = $todayState && in_array($todayState['status'], ['planned', 'upa', 'hdp', 'hd_upa', 'bday']);
        $todayHasAttendance = $todayState && $todayState['check_in_time'] !== null;
        
        $streak = 0;
        $evalDate = today();
        if (!$todayHasAttendance && !$todayIsWeeklyOff && !$todayHasLeave) {
            if ($now->lessThanOrEqualTo($threshold)) {
                $evalDate = today()->subDay();
            }
        }
        
        $current = $evalDate->copy();
        while ($current->gte($streakStartDate)) {
            $dateStr = $current->format('Y-m-d');
            $state = $historyStates[$dateStr] ?? null;
            if (!$state) {
                break;
            }
            
            $status = $state['status'];
            if ($status === 'off' || in_array($status, ['planned', 'upa', 'hdp', 'hd_upa', 'bday'])) {
                $current->subDay();
                continue;
            }
            
            if ($status === 'present') {
                $streak++;
            } else {
                break;
            }
            $current->subDay();
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
            'month_hours' => round($monthHours, 1),
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

        // Fetch 30-day history with exact records from service
        $days = 30;
        $startDate = today()->subDays($days - 1);
        $states = $this->attendanceService->getAttendanceStatesForRange($user, $startDate, today());

        $history = [];
        // Loop in reverse chronological order (from today backwards)
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $state = $states[$dateStr] ?? null;
            
            if ($state) {
                $status = $state['status'];
                if ($status === 'off') {
                    $status = 'weekly_off';
                } elseif (in_array($status, ['planned', 'upa', 'hdp', 'hd_upa', 'bday'])) {
                    $status = 'on_leave';
                }

                $history[] = [
                    'date' => $date,
                    'day_of_week' => $date->format('l'),
                    'is_weekend' => $state['status'] === 'off',
                    'check_in' => $state['check_in_time'],
                    'check_out' => $state['check_out_time'],
                    'status' => $status,
                    'hours' => $state['hours'] > 0 ? $state['hours'] : null,
                    'classification' => $state['classification'],
                    'is_overridden' => $state['is_overridden'],
                ];
            }
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
