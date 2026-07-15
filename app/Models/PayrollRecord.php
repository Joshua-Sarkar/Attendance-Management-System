<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRecord extends Model
{
    protected $fillable = [
        'payroll_cycle_id',
        'user_id',
        'base_salary',
        'gross_salary',
        'net_salary',
        'attendance_deductions',
        'leave_deductions',
        'statutory_deductions',
        'tax_deductions',
        'overtime_hours',
        'overtime_pay',
        'bonuses',
        'allowances',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'unpaid_leave_days',
        'birthday_leave_days',
        'half_days',
        'late_days',
        'wfh_days',
        'status',
        'correction_reason',
        'locked',
        'last_modified_at',
        'last_modified_by_id',
        'calculation_metadata',
        'calculation_version',
        'fingerprint',
        'employee_review_status',
        'employee_approved_at',
        'admin_approved_at',
        'admin_approved_by_id',
        'locked_at',
        'locked_by_id',
        'locked_snapshot',
        'payslip_status',
        'payslip_generated_at',
        'payslip_published_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'attendance_deductions' => 'decimal:2',
        'leave_deductions' => 'decimal:2',
        'statutory_deductions' => 'decimal:2',
        'tax_deductions' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'allowances' => 'decimal:2',
        'present_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
        'leave_days' => 'decimal:2',
        'unpaid_leave_days' => 'decimal:2',
        'birthday_leave_days' => 'decimal:2',
        'locked' => 'boolean',
        'last_modified_at' => 'datetime',
        'calculation_metadata' => 'json',
        'calculation_version' => 'integer',
        'employee_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'locked_snapshot' => 'array',
        'payslip_generated_at' => 'datetime',
        'payslip_published_at' => 'datetime',
    ];

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(PayrollCorrection::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(PayrollException::class);
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by_id');
    }

    public function adminApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(PayrollDispute::class);
    }
}
