<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class AttendanceTimingResolver
{
    /**
     * Resolve the shift start, grace threshold, and shift end for a given user and date.
     *
     * @param User $user
     * @param Carbon $date
     * @return array
     */
    public static function resolveTimings(User $user, Carbon $date): array
    {
        $department = $user->department;

        if ($department) {
            $code = strtolower(trim($department->code ?? ''));
            $name = strtolower(trim($department->name ?? ''));
            $isHealthcare = ($code === 'healthcare' || $name === 'healthcare' || $code === 'hlt' || str_contains($name, 'healthcare') || str_contains($code, 'hlt'));

            if ($isHealthcare) {
                $startTime = $department->shift_start_time;
                if (!$startTime || $startTime === '09:30:00' || $startTime === '09:30') {
                    $startTime = config('attendance.departments.healthcare.start_time', '10:00:00');
                }
                $endTime = $department->shift_end_time;
                if (!$endTime || $endTime === '17:30:00' || $endTime === '17:30') {
                    $endTime = config('attendance.departments.healthcare.end_time', '18:00:00');
                }
                $graceMinutes = $department->grace_minutes !== null ? (int) $department->grace_minutes : (int) config('attendance.departments.healthcare.grace_minutes', 5);
            } else {
                $startTime = $department->shift_start_time ?? config('attendance.start_time', '09:30:00');
                $graceMinutes = $department->grace_minutes !== null ? (int) $department->grace_minutes : (int) config('attendance.grace_minutes', 15);
                $endTime = $department->shift_end_time ?? config('attendance.end_time', '18:30:00');
            }
        } else {
            $transitionDate = config('attendance.new_rules_start_date');
            $useNewRules = false;

            if ($transitionDate) {
                $useNewRules = $date->format('Y-m-d') >= $transitionDate;
            }

            if ($useNewRules) {
                $startTime = config('attendance.start_time', '09:30:00');
                $graceMinutes = (int) config('attendance.grace_minutes', 15);
                $endTime = config('attendance.end_time', '18:30:00');
            } else {
                $startTime = '09:00:00';
                $graceMinutes = 15;
                $endTime = '18:30:00';
            }
        }

        // Ensure time string has format hh:mm:ss
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        $shiftStart = $date->copy()->setTimeFromTimeString($startTime);
        $graceThreshold = $shiftStart->copy()->addMinutes($graceMinutes);
        $shiftEnd = $date->copy()->setTimeFromTimeString($endTime);

        return [
            'start_time' => $startTime,
            'grace_minutes' => $graceMinutes,
            'end_time' => $endTime,
            'shift_start' => $shiftStart,
            'grace_threshold' => $graceThreshold,
            'shift_end' => $shiftEnd,
        ];
    }

    /**
     * Resolve whether a given date is a weekly off day.
     *
     * @param Carbon $date
     * @return bool
     */
    public static function isWeeklyOff(Carbon $date): bool
    {
        $weeklyOffDay = config('attendance.weekly_off_day', 'Sunday');
        return strtolower($date->format('l')) === strtolower($weeklyOffDay);
    }

    /**
     * Resolve whether the worked hours are below the half-day threshold.
     *
     * @param float $hours
     * @return bool
     */
    public static function isInsufficientHours(float $hours): bool
    {
        $threshold = config('attendance.half_day_threshold_hours', 4.0);
        return $hours < $threshold;
    }
}
