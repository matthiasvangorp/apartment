<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTask extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceTaskFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_done_on' => 'date',
        'next_due_on' => 'date',
        'cadence_months' => 'int',
    ];

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(Appliance::class);
    }
}
