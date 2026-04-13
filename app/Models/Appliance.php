<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appliance extends Model
{
    /** @use HasFactory<\Database\Factories\ApplianceFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'purchased_on' => 'date',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'manual_document_id');
    }

    public function maintenanceTasks(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class);
    }
}
