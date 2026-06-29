<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * Record check-in for an employee.
     * Creates or updates today's attendance record.
     */
    public function checkIn(User $user): Attendance
    {
        $today = today();
        $now = now();

        $department = $user->department;
        $startTime = $department?->shift_start_time ?? '09:30:00';
        $graceMinutes = $department?->grace_minutes ?? 5;

        $threshold = today()->setTimeFromTimeString($startTime)->addMinutes($graceMinutes);

        $nowMin = $now->copy()->second(0)->microsecond(0);
        $thresholdMin = $threshold->copy()->second(0)->microsecond(0);

        if ($nowMin->greaterThan($thresholdMin)) {
            $status = 'late';
            $classification = 'half_day';
            $reason = 'late_arrival';
        } else {
            $status = 'present';
            $classification = 'full_day';
            $reason = null;
        }

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'status' => $status,
                'automatic_status' => $status,
                'classification' => $classification,
                'automatic_classification' => $classification,
                'automatic_classification_reason' => $reason,
            ]
        );

        // Only set check-in time if not already checked in
        if (is_null($attendance->check_in_time)) {
            $attendance->check_in_time = $now;
            $attendance->status = $status;
            $attendance->automatic_status = $status;
            $attendance->classification = $classification;
            $attendance->automatic_classification = $classification;
            $attendance->automatic_classification_reason = $reason;
            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Record check-out for an employee.
     * Updates today's attendance record with check-out time.
     */
    public function checkOut(User $user): Attendance
    {
        $today = today();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        // Only set check-out time if not already checked out
        if (is_null($attendance->check_out_time)) {
            $attendance->check_out_time = now();

            // Calculate hours worked
            $hours = $attendance->check_in_time->diffInMinutes($attendance->check_out_time, true) / 60.0;

            // Check if it's already classified as half_day due to late_arrival
            if ($attendance->automatic_classification_reason !== 'late_arrival') {
                if ($hours < 4.0) {
                    $attendance->automatic_classification = 'half_day';
                    $attendance->automatic_classification_reason = 'insufficient_hours';
                    if (!$attendance->is_overridden) {
                        $attendance->classification = 'half_day';
                    }
                } else {
                    $attendance->automatic_classification = 'full_day';
                    $attendance->automatic_classification_reason = null;
                    if (!$attendance->is_overridden) {
                        $attendance->classification = 'full_day';
                    }
                }
            }

            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Get today's attendance record for a user.
     */
    public function getTodayAttendance(User $user): ?Attendance    {
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if (!$attendance) {
            $todayStr = today()->format('Y-m-d');
            $leave = \App\Models\LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $todayStr . ' 23:59:59')
                ->where('end_date', '>=', $todayStr . ' 00:00:00')
                ->first();

            if ($leave) {
                $status = $leave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave';
                $attendance = new Attendance([
                    'user_id' => $user->id,
                    'date' => today(),
                    'status' => $status,
                    'automatic_status' => $status,
                    'classification' => 'full_day',
                    'automatic_classification' => 'full_day',
                ]);
            } elseif (today()->isSunday()) {
                $attendance = new Attendance([
                    'user_id' => $user->id,
                    'date' => today(),
                    'status' => 'weekly_off',
                    'automatic_status' => 'weekly_off',
                    'classification' => 'full_day',
                    'automatic_classification' => 'full_day',
                ]);
            }
        }

        return $attendance;
    }


    /**
     * Get attendance history for a user over last N days.
     */
    public function getAttendanceHistory(User $user, int $days = 30): Collection
    {
        return Attendance::where('user_id', $user->id)
            ->where('date', '>=', today()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Calculate total hours worked today.
     */
    public function calculateTodayHours(User $user): ?float
    {
        $attendance = $this->getTodayAttendance($user);

        if (!$attendance || !$attendance->check_in_time || !$attendance->check_out_time) {
            return null;
        }

        return $attendance->check_in_time->diffInHours($attendance->check_out_time, absolute: true);
    }

    /**
     * Check if user is already checked in today.
     */
    public function isCheckedInToday(User $user): bool
    {
        $attendance = $this->getTodayAttendance($user);
        return $attendance && !is_null($attendance->check_in_time);
    }

    /**
     * Check if user has checked out today.
     */
    public function hasCheckedOutToday(User $user): bool
    {
        $attendance = $this->getTodayAttendance($user);
        return $attendance && !is_null($attendance->check_out_time);
    }

    /**
     * Get filtered list of active employees and their attendance record for a specific date.
     */
    public function getFilteredAttendance(string $date, ?int $departmentId = null, ?string $searchQuery = null, ?User $monitoringUser = null): \Illuminate\Support\Collection
    {
        $query = User::where('status', 'active')
            ->with(['department', 'manager', 'admin']);

        if ($monitoringUser && $monitoringUser->role === 'manager') {
            // Managers only see active employees assigned to them
            $query->where('role', 'employee')
                  ->where('manager_id', $monitoringUser->id);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('employee_id', 'like', '%' . $searchQuery . '%');
            });
        }

        $employees = $query->orderBy('name')->get();

        // Eager load the attendance records for the specific date
        $attendances = Attendance::where('date', \Carbon\Carbon::parse($date)->startOfDay())
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        // Eager load the approved leave requests overlapping this date
        $leaves = \App\Models\LeaveRequest::whereIn('user_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->where('start_date', '<=', $date . ' 23:59:59')
            ->where('end_date', '>=', $date . ' 00:00:00')
            ->get()
            ->keyBy('user_id');

        // Map them together
        $isSunday = \Carbon\Carbon::parse($date)->isSunday();
        return $employees->map(function ($employee) use ($attendances, $leaves, $date, $isSunday) {
            $attendance = $attendances->get($employee->id);
            $leave = $leaves->get($employee->id);

            if (!$attendance) {
                if ($leave) {
                    $status = $leave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave';
                    $attendance = new \App\Models\Attendance([
                        'user_id' => $employee->id,
                        'date' => \Carbon\Carbon::parse($date),
                        'status' => $status,
                        'automatic_status' => $status,
                        'classification' => 'full_day',
                        'automatic_classification' => 'full_day',
                    ]);
                } elseif ($isSunday) {
                    $attendance = new \App\Models\Attendance([
                        'user_id' => $employee->id,
                        'date' => \Carbon\Carbon::parse($date),
                        'status' => 'weekly_off',
                        'automatic_status' => 'weekly_off',
                        'classification' => 'full_day',
                        'automatic_classification' => 'full_day',
                    ]);
                }
            }

            $employee->today_attendance = $attendance;
            return $employee;
        });
    }

    /**
     * Compute overview stats for the dashboard.
     */
    public function getTodayStats(string $date, ?int $departmentId = null, ?User $monitoringUser = null): array    {
        $employees = $this->getFilteredAttendance($date, $departmentId, null, $monitoringUser);

        $present = 0;
        $late = 0;
        $absent = 0;
        $onLeave = 0;
        $wfh = 0;

        $lateArrivals = [];
        $exceptions = [
            'on_leave' => [],
            'wfh' => [],
            'late' => [],
        ];
        $totalLateMinutes = 0;

        $isSunday = \Carbon\Carbon::parse($date)->isSunday();

        foreach ($employees as $emp) {
            $attendance = $emp->today_attendance;
            
            // Resolve resolved status and whether overridden
            $status = 'absent';
            $isOverridden = false;
            
            if ($attendance) {
                $status = $attendance->status;
                $isOverridden = $attendance->is_overridden;
            } elseif ($isSunday) {
                $status = 'weekly_off';
            }

            if ($status === 'present') {
                $present++;
            } elseif ($status === 'late') {
                $late++;
                $present++;
                
                $lateMinutes = $attendance ? $attendance->late_minutes : 0;
                $totalLateMinutes += $lateMinutes;
                $lateArrivals[] = [
                    'name' => $emp->name,
                    'employee_id' => $emp->employee_id,
                    'check_in_time' => $attendance?->check_in_time,
                    'late_minutes' => $lateMinutes,
                ];
                
                $exceptions['late'][] = [
                    'name' => $emp->name,
                    'employee_id' => $emp->employee_id,
                    'check_in_time' => $attendance?->check_in_time,
                    'late_minutes' => $lateMinutes,
                ];
            } elseif ($status === 'absent') {
                if (!$isSunday || $isOverridden) {
                    $absent++;
                }
            } elseif ($status === 'on_leave') {
                $onLeave++;
                $exceptions['on_leave'][] = [
                    'name' => $emp->name,
                    'employee_id' => $emp->employee_id,
                ];
            } elseif ($status === 'wfh') {
                $wfh++;
                $exceptions['wfh'][] = [
                    'name' => $emp->name,
                    'employee_id' => $emp->employee_id,
                ];
            }
        }

        $averageLateMinutes = $late > 0 ? round($totalLateMinutes / $late, 1) : 0;

        return [
            'total' => $employees->count(),
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'on_leave' => $onLeave,
            'wfh' => $wfh,
            'late_arrivals' => $lateArrivals,
            'average_late_minutes' => $averageLateMinutes,
            'exceptions' => $exceptions,
        ];
    }

    /**
     * Calculate stats for an employee over the last N days.
     */
    public function getEmployeeStats(User $user, int $days = 30): array
    {
        $startDate = today()->subDays($days - 1);
        $attendances = Attendance::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', today())
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));

        $leaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', today()->format('Y-m-d') . ' 23:59:59')
            ->where('end_date', '>=', $startDate->format('Y-m-d') . ' 00:00:00')
            ->get();

        $present = 0;
        $late = 0;
        $absent = 0;
        $onLeave = 0;
        $wfh = 0;
        $totalHours = 0.0;

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $record = $attendances->get($dateStr);
            
            $status = 'absent';
            $isOverridden = false;
            $hasRecord = false;
            
            if ($record) {
                $status = $record->status;
                $isOverridden = $record->is_overridden;
                $hasRecord = true;
            } else {
                $dayLeave = $leaves->first(function($leave) use ($date) {
                    $dateStr = $date->format('Y-m-d');
                    $leaveStartStr = $leave->start_date->format('Y-m-d');
                    $leaveEndStr = $leave->end_date->format('Y-m-d');
                    return $dateStr >= $leaveStartStr && $dateStr <= $leaveEndStr;
                });
                
                if ($dayLeave) {
                    $status = $dayLeave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave';
                } elseif ($date->isSunday()) {
                    $status = 'weekly_off';
                }
            }

            if ($status === 'weekly_off') {
                continue;
            }
            
            if ($status === 'present') {
                $present++;
            } elseif ($status === 'late') {
                $late++;
                $present++;
            } elseif ($status === 'absent') {
                if (!$date->isSunday() || $isOverridden) {
                    $absent++;
                }
            } elseif ($status === 'on_leave') {
                $onLeave++;
            } elseif ($status === 'wfh') {
                $wfh++;
            }

            if ($hasRecord && $record->check_in_time) {
                $endTime = $record->check_out_time ?? now();
                $totalHours += $record->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0;
            }
        }

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'on_leave' => $onLeave,
            'wfh' => $wfh,
            'total_hours' => $totalHours,
        ];
    }


    /**
     * Get recent check-in/out activity across all employees.
     */
    public function getRecentActivity(int $limit = 5, ?User $monitoringUser = null): \Illuminate\Support\Collection
    {
        $checkInQuery = Attendance::whereNotNull('check_in_time')->with('user');
        $checkOutQuery = Attendance::whereNotNull('check_out_time')->with('user');

        if ($monitoringUser && $monitoringUser->role === 'manager') {
            $checkInQuery->whereHas('user', function($q) use ($monitoringUser) {
                $q->where('role', 'employee')->where('manager_id', $monitoringUser->id);
            });
            $checkOutQuery->whereHas('user', function($q) use ($monitoringUser) {
                $q->where('role', 'employee')->where('manager_id', $monitoringUser->id);
            });
        }

        $recentCheckIns = $checkInQuery->orderBy('check_in_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($att) => [
                'employee_name' => $att->user?->name ?? 'Unknown',
                'employee_id' => $att->user?->employee_id ?? 'N/A',
                'action' => 'Checked In',
                'time' => $att->check_in_time,
                'timestamp' => $att->check_in_time->format('h:i A'),
            ]);

        $recentCheckOuts = $checkOutQuery->orderBy('check_out_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($att) => [
                'employee_name' => $att->user?->name ?? 'Unknown',
                'employee_id' => $att->user?->employee_id ?? 'N/A',
                'action' => 'Checked Out',
                'time' => $att->check_out_time,
                'timestamp' => $att->check_out_time->format('h:i A'),
            ]);

        return $recentCheckIns->concat($recentCheckOuts)
            ->sortByDesc('time')
            ->take($limit)
            ->values();
    }
}
