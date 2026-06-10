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
        
        return view('attendance.employee-dashboard', [
            'user' => $user,
            'today_attendance' => $today_attendance,
            'recent_history' => $recent_history,
            'hours_today' => $hours_today,
            'is_checked_in' => $this->attendanceService->isCheckedInToday($user),
            'is_checked_out' => $this->attendanceService->hasCheckedOutToday($user),
        ]);
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
        $present_count = $history->where('status', 'present')->count();
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
