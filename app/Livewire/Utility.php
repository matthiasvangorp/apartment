<?php

namespace App\Livewire;

use App\Apartment\Analytics\UtilityAggregator;
use App\Models\UtilityReading;
use App\Models\UtilityStat;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Utility · Apartment')]
class Utility extends Component
{
    #[Url(as: 'type')]
    public string $type = 'electricity';

    public function render(UtilityAggregator $aggregator)
    {
        $availableTypes = UtilityReading::query()
            ->select('utility_type')
            ->distinct()
            ->orderBy('utility_type')
            ->pluck('utility_type')
            ->all();

        if (! in_array($this->type, $availableTypes, true) && ! empty($availableTypes)) {
            $this->type = $availableTypes[0];
        }

        $stat = UtilityStat::where('utility_type', $this->type)->orderByDesc('window_end')->first()
            ?? $aggregator->computeFor($this->type);

        $readings = UtilityReading::where('utility_type', $this->type)
            ->whereNotNull('consumption_value')
            ->whereNotNull('period_end')
            ->orderBy('period_end')
            ->get();

        $chartData = $readings->map(fn ($r) => [
            'period_end' => $r->period_end->toDateString(),
            'consumption' => (float) $r->consumption_value,
            'amount_huf' => $r->amount_huf !== null ? (float) $r->amount_huf : null,
        ])->values()->all();

        return view('livewire.utility', [
            'availableTypes' => $availableTypes,
            'stat' => $stat,
            'readings' => $readings,
            'chartData' => $chartData,
        ]);
    }
}
