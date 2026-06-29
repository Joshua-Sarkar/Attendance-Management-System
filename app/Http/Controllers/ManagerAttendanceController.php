<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerAttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Show the attendance detail page for a specific employee.
     */
    public function show(Request $request, User $user): mixed
    {
        $currentUser = $request->user();

        // Access control: only admins and managers can view this page
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        // If manager, they can only view employees assigned to them
        if ($currentUser->role === 'manager') {
            if ($user->role !== 'employee' || $user->manager_id !== $currentUser->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Eager-load relations for profile card
        $user->load(['department', 'manager', 'admin']);

        // Fetch stats (last 30 days)
        $stats = $this->attendanceService->getEmployeeStats($user, 30);

        // Fetch 30-day history with exact records and dynamic absence/weekend fallback
        $days = 30;
        $startDate = today()->subDays($days - 1);
        $attendances = Attendance::where('user_id', $user->id)
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

            $status = $record ? $record->status : ($date->isSunday() ? 'weekly_off' : 'absent');

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

        $totalWorkingDays = $stats['present'] + $stats['absent'] + $stats['on_leave'] + $stats['wfh'];
        $attendancePercentage = $totalWorkingDays > 0 
            ? round(($stats['present'] / $totalWorkingDays) * 100, 1) 
            : 0;

        return view('attendance.show', compact('user', 'stats', 'history', 'attendancePercentage'));
    }
}
