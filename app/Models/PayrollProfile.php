<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollProfile extends Model
{
    protected $fillable = [
        'user_id',
        'base_salary',
        'salary_effective_date',
        'payroll_enabled',
        'last_imported_at',
        'imported_by_id',
        'import_source',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'salary_effective_date' => 'date',
        'payroll_enabled' => 'boolean',
        'last_imported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_id');
    }

    public function salaryHistories(): HasMany
    {
        return $this->hasMany(SalaryHistory::class);
    }

    /**
     * Record a new salary revision.
     */
    public function recordSalaryRevision(float $baseSalary, string $effectiveDate, ?string $changeReason = null, ?int $changedById = null, string $source = 'Manual'): SalaryHistory
    {
        $history = $this->salaryHistories()->create([
            'base_salary' => $baseSalary,
            'salary_effective_date' => $effectiveDate,
            'change_reason' => $changeReason,
            'changed_by_id' => $changedById,
            'source' => $source,
        ]);

        $this->update([
            'base_salary' => $baseSalary,
            'salary_effective_date' => $effectiveDate,
        ]);

        return $history;
    }
}
