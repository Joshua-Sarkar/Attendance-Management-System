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

        $timings = AttendanceTimingResolver::resolveTimings($user, $today);
        $threshold = $timings['grace_threshold'];

        $nowMin = $now->copy()->second(0)->microsecond(0);
        $thresholdMin = $threshold->copy()->second(0)->microsecond(0);

        $lateArrivalClass = config('attendance.late_arrival_classification', 'half_day');

        if ($nowMin->greaterThan($thresholdMin)) {
            $status = 'late';
            $classification = $lateArrivalClass;
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

            $lateArrivalClass = config('attendance.late_arrival_classification', 'half_day');

            // If the record was classified as a late arrival, and that classification is already half_day, we don't need to override it.
            // Otherwise, or if it wasn't late arrival, we check for insufficient hours.
            if ($attendance->automatic_classification_reason === 'late_arrival' && $lateArrivalClass === 'half_day') {
                // Keep it as half_day and late_arrival
            } else {
                if (AttendanceTimingResolver::isInsufficientHours($hours)) {
                    $attendance->automatic_classification = 'half_day';
                    $attendance->automatic_classification_reason = 'insufficient_hours';
                    if (!$attendance->is_overridden) {
                        $attendance->classification = 'half_day';
                    }
                } else {
                    $attendance->automatic_classification = ($attendance->automatic_classification_reason === 'late_arrival') ? $lateArrivalClass : 'full_day';
                    if (!$attendance->is_overridden) {
                        $attendance->classification = $attendance->automatic_classification;
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
                $metadata = [
                    'is_paid' => $leave->is_paid,
                    'leave_type' => $leave->leave_type,
                ];
                if ($leave->leave_type === 'complimentary') {
                    $metadata['is_birthday'] = true;
                }
                $attendance = new Attendance([
                    'user_id' => $user->id,
                    'date' => today(),
                    'status' => $status,
                    'automatic_status' => $status,
                    'classification' => 'full_day',
                    'automatic_classification' => 'full_day',
                    'metadata' => $metadata,
                ]);
            } elseif (AttendanceTimingResolver::isWeeklyOff(today())) {
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
        $parsedDate = \Carbon\Carbon::parse($date);
        $isWeeklyOff = AttendanceTimingResolver::isWeeklyOff($parsedDate);
        return $employees->map(function ($employee) use ($attendances, $leaves, $parsedDate, $isWeeklyOff) {
            $attendance = $attendances->get($employee->id);
            $leave = $leaves->get($employee->id);

            if (!$attendance) {
                if ($leave) {
                    $status = $leave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave';
                    $metadata = [
                        'is_paid' => $leave->is_paid,
                        'leave_type' => $leave->leave_type,
                    ];
                    if ($leave->leave_type === 'complimentary') {
                        $metadata['is_birthday'] = true;
                    }
                    $attendance = new \App\Models\Attendance([
                        'user_id' => $employee->id,
                        'date' => $parsedDate,
                        'status' => $status,
                        'automatic_status' => $status,
                        'classification' => 'full_day',
                        'automatic_classification' => 'full_day',
                        'metadata' => $metadata,
                    ]);
                } elseif ($isWeeklyOff) {
                    $attendance = new \App\Models\Attendance([
                        'user_id' => $employee->id,
                        'date' => $parsedDate,
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

        $parsedDate = \Carbon\Carbon::parse($date);
        $isWeeklyOff = AttendanceTimingResolver::isWeeklyOff($parsedDate);

        foreach ($employees as $emp) {
            $attendance = $emp->today_attendance;
            
            // Resolve resolved status and whether overridden
            $status = 'absent';
            $isOverridden = false;
            
            if ($attendance) {
                $status = $attendance->status;
                $isOverridden = $attendance->is_overridden;
            } elseif ($isWeeklyOff) {
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
                if (!$isWeeklyOff || $isOverridden) {
                    $absent++;
                }
            } elseif ($status === 'on_leave' || $status === 'paid_leave' || $status === 'unpaid_leave') {
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
                } elseif (AttendanceTimingResolver::isWeeklyOff($date)) {
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
                if (!AttendanceTimingResolver::isWeeklyOff($date) || $isOverridden) {
                    $absent++;
                }
            } elseif ($status === 'on_leave' || $status === 'paid_leave' || $status === 'unpaid_leave') {
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

    /**
     * Resolve the active users list for bulk overrides based on scope parameters.
     */
    public function resolveBulkOverrideUsers(array $params): \Illuminate\Database\Eloquent\Collection
    {
        $scopeType = $params['scope_type'] ?? 'all';
        $query = User::where('status', 'active');

        if ($scopeType === 'employee') {
            $employeeIds = $params['employee_ids'] ?? [];
            if (empty($employeeIds)) {
                return new \Illuminate\Database\Eloquent\Collection();
            }
            $query->whereIn('id', $employeeIds);
        } elseif ($scopeType === 'department') {
            $departmentIds = $params['department_ids'] ?? [];
            if (empty($departmentIds)) {
                return new \Illuminate\Database\Eloquent\Collection();
            }
            $query->whereIn('department_id', $departmentIds);
        }

        return $query->get();
    }

    /**
     * Resolve the dates list for bulk overrides based on mode and options.
     */
    public function resolveBulkOverrideDates(array $params): array
    {
        $dateMode = $params['date_mode'] ?? 'single';
        $dates = [];

        if ($dateMode === 'single') {
            if (!empty($params['date'])) {
                $dates[] = Carbon::parse($params['date'])->startOfDay();
            }
        } elseif ($dateMode === 'range') {
            $startDateStr = $params['start_date'] ?? null;
            $endDateStr = $params['end_date'] ?? null;
            if ($startDateStr && $endDateStr) {
                $start = Carbon::parse($startDateStr)->startOfDay();
                $end = Carbon::parse($endDateStr)->startOfDay();

                $workingDaysOnly = (bool) ($params['working_days_only'] ?? false);
                $includeSundays = (bool) ($params['include_sundays'] ?? false);

                $current = $start->copy();
                while ($current->lte($end)) {
                    $isWeeklyOff = AttendanceTimingResolver::isWeeklyOff($current);

                    if ($workingDaysOnly) {
                        if ($isWeeklyOff) {
                            $dayName = strtolower($current->format('l'));
                            if ($includeSundays && $dayName === 'sunday') {
                                $dates[] = $current->copy();
                            }
                        } else {
                            $dates[] = $current->copy();
                        }
                    } else {
                        $dates[] = $current->copy();
                    }
                    $current->addDay();
                }
            }
        } elseif ($dateMode === 'multiple') {
            $datesArray = $params['dates'] ?? [];
            foreach ($datesArray as $d) {
                if (!empty($d)) {
                    $dates[] = Carbon::parse($d)->startOfDay();
                }
            }
        }

        return collect($dates)
            ->map(fn($d) => $d->startOfDay())
            ->unique(fn($d) => $d->format('Y-m-d'))
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Generate preview information before committing bulk override changes.
     */
    public function getBulkOverridePreview(array $params, User $admin): array
    {
        $users = $this->resolveBulkOverrideUsers($params);
        $dates = $this->resolveBulkOverrideDates($params);

        $userIds = $users->pluck('id')->toArray();
        $dateStrings = collect($dates)->map(fn($d) => $d->format('Y-m-d 00:00:00'))->toArray();

        $employeesSelected = count($userIds);
        $datesSelected = count($dates);
        $attendanceRecordsAffected = $employeesSelected * $datesSelected;

        if ($employeesSelected === 0 || $datesSelected === 0) {
            return [
                'employees_selected' => $employeesSelected,
                'dates_selected' => $datesSelected,
                'attendance_records_affected' => 0,
                'existing_overrides' => 0,
                'existing_leave_records' => 0,
                'records_that_will_change' => 0,
                'has_conflicts' => false,
                'conflict_message' => null,
            ];
        }

        // Fetch existing attendances and leave requests in batch
        $existingAttendances = Attendance::whereIn('user_id', $userIds)
            ->whereIn('date', $dateStrings)
            ->get()
            ->groupBy('user_id');

        $minDate = collect($dates)->first()->format('Y-m-d') . ' 00:00:00';
        $maxDate = collect($dates)->last()->format('Y-m-d') . ' 23:59:59';

        $existingLeaves = \App\Models\LeaveRequest::whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->where('start_date', '<=', $maxDate)
            ->where('end_date', '>=', $minDate)
            ->get()
            ->groupBy('user_id');

        $existingOverrides = 0;
        $existingLeaveRecords = 0;
        $recordsThatWillChange = 0;
        $hasConflicts = false;

        $skipLeaves = (bool) ($params['skip_leaves'] ?? false);
        $skipOverrides = (bool) ($params['skip_overrides'] ?? false);
        $conflictHandling = $params['conflict_handling'] ?? 'cancel';

        $status = $params['status'];
        $classification = $params['classification'] ?? 'full_day';

        if ($status === 'half_day') {
            $status = 'present';
            $classification = 'half_day';
        }

        foreach ($userIds as $userId) {
            foreach ($dates as $date) {
                // Check existing attendance record
                $attendance = null;
                if (isset($existingAttendances[$userId])) {
                    $attendance = $existingAttendances[$userId]->first(function ($att) use ($date) {
                        return Carbon::parse($att->date)->startOfDay()->equalTo($date);
                    });
                }

                // Check existing leave request
                $leave = null;
                if (isset($existingLeaves[$userId])) {
                    $leave = $existingLeaves[$userId]->first(function ($l) use ($date) {
                        $start = Carbon::parse($l->start_date)->startOfDay();
                        $end = Carbon::parse($l->end_date)->startOfDay();
                        return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
                    });
                }

                $hasOverride = $attendance && $attendance->is_overridden;
                $hasLeave = $leave !== null;

                if ($hasOverride) {
                    $existingOverrides++;
                }
                if ($hasLeave) {
                    $existingLeaveRecords++;
                }

                $isConflict = ($hasOverride && !$skipOverrides) || ($hasLeave && !$skipLeaves);
                if ($isConflict) {
                    $hasConflicts = true;
                }

                $shouldSkip = false;
                if ($skipOverrides && $hasOverride) {
                    $shouldSkip = true;
                }
                if ($skipLeaves && $hasLeave) {
                    $shouldSkip = true;
                }
                if ($conflictHandling === 'skip' && $isConflict) {
                    $shouldSkip = true;
                }

                if (!$shouldSkip) {
                    $targetClassification = $classification;
                    if ($targetClassification === 'automatic') {
                        $targetClassification = $attendance ? ($attendance->automatic_classification ?? 'full_day') : 'full_day';
                    }

                    $willChange = false;
                    if (!$attendance) {
                        $willChange = true;
                    } else {
                        if ($attendance->status !== $status || $attendance->classification !== $targetClassification || !$attendance->is_overridden) {
                            $willChange = true;
                        }
                    }

                    if ($willChange) {
                        $recordsThatWillChange++;
                    }
                }
            }
        }

        $conflictMessage = null;
        if ($hasConflicts) {
            $conflictingCount = 0;
            foreach ($userIds as $userId) {
                foreach ($dates as $date) {
                    $attendance = null;
                    if (isset($existingAttendances[$userId])) {
                        $attendance = $existingAttendances[$userId]->first(function ($att) use ($date) {
                            return Carbon::parse($att->date)->startOfDay()->equalTo($date);
                        });
                    }

                    $leave = null;
                    if (isset($existingLeaves[$userId])) {
                        $leave = $existingLeaves[$userId]->first(function ($l) use ($date) {
                            $start = Carbon::parse($l->start_date)->startOfDay();
                            $end = Carbon::parse($l->end_date)->startOfDay();
                            return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
                        });
                    }

                    $hasOverride = $attendance && $attendance->is_overridden;
                    $hasLeave = $leave !== null;

                    if (($hasOverride && !$skipOverrides) || ($hasLeave && !$skipLeaves)) {
                        $conflictingCount++;
                    }
                }
            }

            if ($conflictingCount > 0) {
                if ($conflictHandling === 'cancel') {
                    $conflictMessage = "Error: {$conflictingCount} conflict(s) detected. The operation will be cancelled unless you change conflict handling or skip options.";
                } elseif ($conflictHandling === 'skip') {
                    $conflictMessage = "Notice: {$conflictingCount} conflict(s) detected and will be skipped.";
                } else {
                    $conflictMessage = "Notice: {$conflictingCount} conflict(s) detected and will be replaced.";
                }
            }
        }

        return [
            'employees_selected' => $employeesSelected,
            'dates_selected' => $datesSelected,
            'attendance_records_affected' => $attendanceRecordsAffected,
            'existing_overrides' => $existingOverrides,
            'existing_leave_records' => $existingLeaveRecords,
            'records_that_will_change' => $recordsThatWillChange,
            'has_conflicts' => $hasConflicts,
            'conflict_message' => $conflictMessage,
        ];
    }

    /**
     * Apply bulk attendance overrides with full transaction coverage and validation.
     */
    public function applyBulkOverride(array $params, User $admin): array
    {
        $users = $this->resolveBulkOverrideUsers($params);
        $dates = $this->resolveBulkOverrideDates($params);

        $userIds = $users->pluck('id')->toArray();
        $dateStrings = collect($dates)->map(fn($d) => $d->format('Y-m-d 00:00:00'))->toArray();

        if (count($userIds) === 0 || count($dates) === 0) {
            throw new \Exception("No employees or dates selected for the override operation.");
        }

        $skipLeaves = (bool) ($params['skip_leaves'] ?? false);
        $skipOverrides = (bool) ($params['skip_overrides'] ?? false);
        $conflictHandling = $params['conflict_handling'] ?? 'cancel';

        $status = $params['status'];
        $classification = $params['classification'] ?? 'full_day';

        if ($status === 'half_day') {
            $status = 'present';
            $classification = 'half_day';
        }

        $reason = $params['override_reason'] ?? '';
        if (strlen($reason) < 5) {
            throw new \Exception("The override reason is mandatory and must be at least 5 characters.");
        }

        $now = now();
        $overrideType = 'bulk';
        if (count($userIds) === 1 && count($dates) === 1) {
            $overrideType = 'individual';
        }

        $appliedCount = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $users, $dates, $dateStrings, $skipLeaves, $skipOverrides, $conflictHandling,
            $status, $classification, $reason, $overrideType, $admin, $now, &$appliedCount, $userIds
        ) {
            $minDate = collect($dates)->first()->format('Y-m-d') . ' 00:00:00';
            $maxDate = collect($dates)->last()->format('Y-m-d') . ' 23:59:59';
            $savedAttendances = [];

            $existingLeaves = \App\Models\LeaveRequest::whereIn('user_id', $users->pluck('id'))
                ->where('status', 'approved')
                ->where('start_date', '<=', $maxDate)
                ->where('end_date', '>=', $minDate)
                ->get()
                ->groupBy('user_id');

            foreach ($users as $user) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                $existingAttendances = Attendance::where('user_id', $lockedUser->id)
                    ->whereIn('date', $dateStrings)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(fn($att) => Carbon::parse($att->date)->format('Y-m-d'));

                foreach ($dates as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $attendance = $existingAttendances->get($dateStr);

                    $leave = null;
                    if (isset($existingLeaves[$lockedUser->id])) {
                        $leave = $existingLeaves[$lockedUser->id]->first(function ($l) use ($date) {
                            return Carbon::parse($l->start_date)->startOfDay()->lte($date) &&
                                Carbon::parse($l->end_date)->endOfDay()->gte($date);
                        });
                    }

                    // Checks and policy application
                    if ($skipLeaves && $leave) {
                        continue;
                    }
                    if ($skipOverrides && $attendance && $attendance->is_overridden) {
                        continue;
                    }

                    if ($conflictHandling === 'cancel' && ($leave || ($attendance && $attendance->is_overridden))) {
                        $conflictType = $leave ? 'approved leave' : 'existing override';
                        throw new \Exception("Operation cancelled due to conflict on {$dateStr} for {$lockedUser->name} ({$conflictType}).");
                    }

                    // Leave balance check/adjustment
                    $alreadyDeducted = 0.0;
                    if ($attendance && $attendance->is_overridden && $attendance->status === 'paid_leave') {
                        $alreadyDeducted = $attendance->classification === 'half_day' ? 0.5 : 1.0;
                    } elseif ($leave && $leave->leave_type !== 'complimentary' && $leave->is_paid) {
                        $alreadyDeducted = 1.0;
                    }

                    $targetClassification = $classification;
                    if ($targetClassification === 'automatic') {
                        $targetClassification = $attendance ? ($attendance->automatic_classification ?? 'full_day') : 'full_day';
                    }

                    $targetDeduction = 0.0;
                    if ($status === 'paid_leave') {
                        $targetDeduction = $targetClassification === 'half_day' ? 0.5 : 1.0;
                    }

                    $adjustment = $alreadyDeducted - $targetDeduction;

                    if ($adjustment < 0.0) {
                        $allowNegative = (bool) config('attendance.allow_negative_leave_balance', true);
                        $netDeduction = abs($adjustment);
                        if (!$allowNegative && ($lockedUser->leave_balance - $netDeduction < 0.0)) {
                            throw new \Exception("Insufficient leave balance for {$lockedUser->name} ({$lockedUser->employee_id}). Balance is {$lockedUser->leave_balance} but this override requires deducting {$netDeduction} leave day(s).");
                        }
                    }

                    if ($adjustment != 0.0) {
                        $lockedUser->leave_balance += $adjustment;
                        $lockedUser->save();

                        \App\Models\LeaveLedgerEntry::create([
                            'user_id' => $lockedUser->id,
                            'amount' => $adjustment,
                            'type' => 'adjustment',
                            'description' => "Adjustment due to attendance override on " . $dateStr . " to status: {$status} (classification: {$targetClassification})",
                        ]);
                    }

                    // Preserve automatic values if not previously present
                    if (!$attendance) {
                        $isWeeklyOff = \App\Services\AttendanceTimingResolver::isWeeklyOff($date);
                        $autoStatus = $leave ? ($leave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave') : ($isWeeklyOff ? 'weekly_off' : 'absent');
                        $autoClassification = 'full_day';

                        $attendance = new Attendance([
                            'user_id' => $lockedUser->id,
                            'date' => $date,
                            'automatic_status' => $autoStatus,
                            'automatic_classification' => $autoClassification,
                            'automatic_classification_reason' => null,
                        ]);
                    } else {
                        if (is_null($attendance->automatic_status)) {
                            $attendance->automatic_status = $attendance->status;
                        }
                        if (is_null($attendance->automatic_classification)) {
                            $attendance->automatic_classification = $attendance->classification;
                        }
                    }

                    $attendance->status = $status;
                    $attendance->classification = $targetClassification;
                    $attendance->is_overridden = true;
                    $attendance->overridden_by = $admin->id;
                    $attendance->overridden_at = $now;
                    $attendance->override_reason = $reason;
                    $attendance->override_type = $overrideType;
                    $attendance->save();

                    $savedAttendances[] = $attendance;
                    $appliedCount++;
                }
            }

            // Save operation metadata on the first record in the group
            if (count($savedAttendances) > 0) {
                $firstAttendance = $savedAttendances[0];
                $firstAttendance->metadata = [
                    'conflict_strategy' => $conflictHandling,
                    'dates_affected' => collect($dates)->map(fn($d) => $d->format('Y-m-d'))->toArray(),
                    'employees_count' => count($userIds),
                    'records_modified' => count($savedAttendances),
                ];
                $firstAttendance->save();
            }
        });

        return [
            'applied_count' => $appliedCount,
        ];
    }
}
