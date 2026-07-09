<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTimelineEntry extends Model
{
    protected $table = 'employee_timeline_entries';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'entry_date',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    /**
     * Get the user that owns the timeline entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
