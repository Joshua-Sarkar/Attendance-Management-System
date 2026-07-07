<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    protected $table = 'salary_histories';

    protected $fillable = [
        'payroll_profile_id',
        'base_salary',
        'salary_effective_date',
        'change_reason',
        'changed_by_id',
        'source',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'salary_effective_date' => 'date',
    ];

    public function payrollProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollProfile::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
