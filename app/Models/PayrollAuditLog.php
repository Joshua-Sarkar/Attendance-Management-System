<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'category',
        'old_value',
        'new_value',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Record a new audit log entry.
     */
    public static function record(?int $userId, ?int $actorId, string $action, string $category, ?string $oldValue = null, ?string $newValue = null, ?string $reason = null): self
    {
        return self::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'action' => $action,
            'category' => $category,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
        ]);
    }
}
