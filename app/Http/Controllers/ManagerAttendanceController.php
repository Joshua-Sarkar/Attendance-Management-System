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

            $history[] = [
                'date' => $date,
                'day_of_week' => $date->format('l'),
                'is_weekend' => $date->isWeekend(),
                'check_in' => $record?->check_in_time,
                'check_out' => $record?->check_out_time,
                'status' => $record ? $record->status : ($date->isWeekend() ? 'weekend' : 'absent'),
                'hours' => $hours,
            ];
        }

        return view('attendance.show', compact('user', 'stats', 'history'));
    }
}
