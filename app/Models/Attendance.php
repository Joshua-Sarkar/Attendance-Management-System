<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'classification',
        'is_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'override_type',
        'automatic_status',
        'automatic_classification',
        'automatic_classification_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'is_overridden' => 'boolean',
        'overridden_at' => 'datetime',
    ];

    protected $appends = [
        'late_minutes',
    ];

    /**
     * Get the number of minutes late, calculated from the end of the grace period.
     */
    public function getLateMinutesAttribute(): int
    {
        if ($this->status !== 'late' || is_null($this->check_in_time)) {
            return 0;
        }

        $department = $this->user?->department;
        if ($department && $department->shift_start_time) {
            $startTime = $department->shift_start_time;
            $graceMinutes = $department->grace_minutes ?? 5;
        } else {
            $transitionDate = config('attendance.new_rules_start_date');
            $useNewRules = false;

            if ($transitionDate) {
                $useNewRules = $this->date->format('Y-m-d') >= $transitionDate;
            }

            if ($useNewRules) {
                $startTime = config('attendance.start_time', '09:30');
                $graceMinutes = config('attendance.grace_minutes', 15);
            } else {
                $startTime = '09:00';
                $graceMinutes = 15;
            }
        }

        $checkIn = \Carbon\Carbon::parse($this->check_in_time);
        $shiftStart = $checkIn->copy()->setTimeFromTimeString($startTime);
        $graceEnd = $shiftStart->copy()->addMinutes($graceMinutes);

        $checkInMin = $checkIn->copy()->second(0)->microsecond(0);
        $graceEndMin = $graceEnd->copy()->second(0)->microsecond(0);

        if ($checkInMin->lte($graceEndMin)) {
            return 0;
        }

        return (int) abs($checkInMin->diffInMinutes($graceEndMin, false));
    }

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
