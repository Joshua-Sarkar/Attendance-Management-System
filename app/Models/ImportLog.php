<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'filename',
        'run_by_user_id',
        'rows_processed',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'duration_seconds',
        'errors',
        'metadata',
    ];

    protected $casts = [
        'errors' => 'array',
        'metadata' => 'array',
        'duration_seconds' => 'decimal:2',
    ];

    public function runByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by_user_id');
    }
}
