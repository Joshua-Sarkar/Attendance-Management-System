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
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'is_overridden' => 'boolean',
        'overridden_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'late_minutes',
    ];

    public function getLateMinutesAttribute(): int
    {
        if (!in_array($this->status, ['late', 'half']) || is_null($this->check_in_time)) {
            return 0;
        }

        $timings = \App\Services\AttendanceTimingResolver::resolveTimings($this->user, $this->date);

        $checkIn = \Carbon\Carbon::parse($this->check_in_time);
        $graceEnd = $timings['grace_threshold'];

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

    /**
     * Get the administrator who performed the override.
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
