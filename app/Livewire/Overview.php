<?php

namespace App\Livewire;

use App\Apartment\Analytics\UtilityAggregator;
use App\Models\Appliance;
use App\Models\Document;
use App\Models\MaintenanceTask;
use App\Models\UtilityReading;
use App\Models\UtilityStat;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Overview · Apartment')]
class Overview extends Component
{
    public function render(UtilityAggregator $aggregator)
    {
        $stat = UtilityStat::where('utility_type', 'electricity')
            ->orderByDesc('window_end')
            ->first()
            ?? $aggregator->computeFor('electricity');

        $trend = UtilityReading::where('utility_type', 'electricity')
            ->whereNotNull('consumption_value')
            ->whereNotNull('period_end')
            ->orderBy('period_end')
            ->get(['period_end', 'consumption_value', 'amount_huf'])
            ->map(fn ($r) => [
                'period_end' => $r->period_end->toDateString(),
                'consumption' => (float) $r->consumption_value,
                'amount_huf' => $r->amount_huf !== null ? (float) $r->amount_huf : null,
            ])
            ->values()
            ->all();

        return view('livewire.overview', [
            'metrics' => [
                'documents' => Document::count(),
                'electricity_last' => $stat?->last_value !== null ? (float) $stat->last_value : null,
                'electricity_avg' => $stat?->rolling_avg_12m !== null ? (float) $stat->rolling_avg_12m : null,
                'electricity_anomaly' => (bool) ($stat?->anomaly ?? false),
                'tasks_due' => MaintenanceTask::whereNotNull('next_due_on')
                    ->where('next_due_on', '<=', now()->addDays(90)->toDateString())
                    ->count(),
                'appliances' => Appliance::count(),
            ],
            'trend' => $trend,
            'upcoming' => MaintenanceTask::with('appliance:id,name,location')
                ->whereNotNull('next_due_on')
                ->orderBy('next_due_on')
                ->limit(5)
                ->get(),
            'recent' => Document::orderByDesc('ingested_at')->limit(8)->get(),
        ]);
    }
}
