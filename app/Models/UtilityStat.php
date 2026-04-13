<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilityStat extends Model
{
    /** @use HasFactory<\Database\Factories\UtilityStatFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'window_end' => 'date',
        'rolling_avg_12m' => 'decimal:3',
        'last_value' => 'decimal:3',
        'yoy_delta' => 'decimal:4',
        'anomaly' => 'bool',
        'computed_at' => 'datetime',
    ];
}
