<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'shift_start_time',
        'shift_end_time',
        'grace_minutes',
    ];

    protected $casts = [
        'grace_minutes' => 'integer',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }
}