<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportProfile extends Model
{
    protected $fillable = [
        'name',
        'source_system',
        'version',
        'is_default',
        'mappings',
        'matching_weights',
    ];

    protected $casts = [
        'mappings' => 'array',
        'matching_weights' => 'array',
        'is_default' => 'boolean',
    ];
}
