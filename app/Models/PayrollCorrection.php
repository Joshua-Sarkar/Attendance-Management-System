<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollCorrection extends Model
{
    protected $fillable = [
        'payroll_cycle_id',
        'payroll_record_id',
        'user_id',
        'type',
        'old_net_salary',
        'new_net_salary',
        'financial_delta',
        'reason',
        'created_by_id',
        'approval_status',
        'approved_by_id',
        'approved_at',
    ];

    protected $casts = [
        'old_net_salary' => 'decimal:2',
        'new_net_salary' => 'decimal:2',
        'financial_delta' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
