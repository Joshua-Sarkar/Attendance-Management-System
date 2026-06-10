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

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'status' => 'present',
            ]
        );

        // Only set check-in time if not already checked in
        if (is_null($attendance->check_in_time)) {
            $attendance->check_in_time = now();
            $attendance->status = 'present';
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
            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Get today's attendance record for a user.
     */
    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();
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
}
