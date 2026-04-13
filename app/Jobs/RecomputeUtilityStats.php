<?php

namespace App\Jobs;

use App\Apartment\Analytics\UtilityAggregator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecomputeUtilityStats implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function handle(UtilityAggregator $aggregator): void
    {
        $stats = $aggregator->recomputeAll();

        Log::channel('apartment')->info('utility.recomputed', [
            'count' => count($stats),
            'types' => collect($stats)->pluck('utility_type')->all(),
            'anomalies' => collect($stats)->where('anomaly', true)->pluck('utility_type')->all(),
        ]);
    }
}
