<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'user_id',
        'planned_leave',
        'unplanned_leave',
        'paternity_leave',
        'maternity_leave',
        'compensatory_leave',
        'pending_leave',
        'utilized_leave',
        'carry_forward',
        'remaining_leave',
        'last_imported_at',
        'imported_by_id',
        'import_source',
    ];

    protected $casts = [
        'planned_leave' => 'decimal:2',
        'unplanned_leave' => 'decimal:2',
        'paternity_leave' => 'decimal:2',
        'maternity_leave' => 'decimal:2',
        'compensatory_leave' => 'decimal:2',
        'pending_leave' => 'decimal:2',
        'utilized_leave' => 'decimal:2',
        'carry_forward' => 'decimal:2',
        'remaining_leave' => 'decimal:2',
        'last_imported_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($leaveBalance) {
            if ($leaveBalance->wasChanged('remaining_leave')) {
                $user = $leaveBalance->user;
                if ($user && (float)$user->leave_balance !== (float)$leaveBalance->remaining_leave) {
                    $user->leave_balance = $leaveBalance->remaining_leave;
                    $user->saveQuietly();
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_id');
    }

    /**
     * Compute total leave dynamically by summing allocations.
     */
    public function getTotalLeaveAttribute(): float
    {
        return (float) (
            $this->planned_leave +
            $this->unplanned_leave +
            $this->paternity_leave +
            $this->maternity_leave +
            $this->compensatory_leave
        );
    }
}
