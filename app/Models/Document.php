<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'issued_on' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'ingested_at' => 'datetime',
        'amount_huf' => 'decimal:2',
        'meta' => 'array',
    ];

    public function utilityReadings(): HasMany
    {
        return $this->hasMany(UtilityReading::class);
    }

    public function linkedAppliance(): HasOne
    {
        return $this->hasOne(Appliance::class, 'manual_document_id');
    }
}
