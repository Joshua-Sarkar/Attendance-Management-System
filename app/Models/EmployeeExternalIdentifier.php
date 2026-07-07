<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeExternalIdentifier extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'external_identifier',
        'identifier_type',
        'is_active',
        'verified_by_id',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }
}
