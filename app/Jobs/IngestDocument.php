<?php

namespace App\Jobs;

use App\Apartment\Ingest\IngestionPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IngestDocument implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly string $absolutePath) {}

    public function handle(IngestionPipeline $pipeline): void
    {
        $pipeline->process($this->absolutePath);
    }
}
