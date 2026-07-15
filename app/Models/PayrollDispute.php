<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDispute extends Model
{
    protected $fillable = [
        'payroll_record_id',
        'user_id',
        'category',
        'affected_date',
        'description',
        'expected_correction',
        'status',
        'resolved_at',
        'resolved_by_id',
        'resolution_notes',
    ];

    protected $casts = [
        'affected_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
