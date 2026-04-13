<?php

namespace App\Apartment\Analytics;

use App\Models\UtilityReading;
use App\Models\UtilityStat;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class UtilityAggregator
{
    public const ANOMALY_RATIO = 1.3;

    public const ANOMALY_LOOKBACK = 6;

    /**
     * Recompute aggregates for every utility type that has at least one usable reading.
     * Returns the stats rows that were upserted.
     *
     * @return list<UtilityStat>
     */
    public function recomputeAll(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $types = UtilityReading::query()
            ->whereNotNull('consumption_value')
            ->whereNotNull('period_end')
            ->distinct()
            ->pluck('utility_type')
            ->all();

        $out = [];
        foreach ($types as $type) {
            if ($stat = $this->computeFor($type, $now)) {
                $out[] = $stat;
            }
        }

        return $out;
    }

    public function computeFor(string $utilityType, ?CarbonImmutable $now = null): ?UtilityStat
    {
        $now ??= CarbonImmutable::now();

        $readings = UtilityReading::query()
            ->where('utility_type', $utilityType)
            ->whereNotNull('consumption_value')
            ->whereNotNull('period_end')
            ->orderBy('period_end')
            ->get();

        if ($readings->isEmpty()) {
            return null;
        }

        $latest = $readings->last();
        $rolling12 = $this->rolling12mAverage($readings, $now);
        $yoyDelta = $this->yoyDelta($readings, $latest);
        $anomaly = $this->isAnomaly($readings, $latest);

        return UtilityStat::updateOrCreate(
            ['utility_type' => $utilityType, 'window_end' => $latest->period_end->startOfDay()],
            [
                'rolling_avg_12m' => $rolling12,
                'last_value' => (float) $latest->consumption_value,
                'yoy_delta' => $yoyDelta,
                'anomaly' => $anomaly,
                'computed_at' => $now,
            ]
        );
    }

    private function rolling12mAverage(Collection $readings, CarbonImmutable $now): ?float
    {
        $cutoff = $now->subMonths(12);
        $window = $readings->filter(fn ($r) => $r->period_end >= $cutoff);
        if ($window->isEmpty()) {
            return null;
        }

        return round((float) $window->avg('consumption_value'), 3);
    }

    /**
     * Year-over-year delta: latest reading vs the reading whose period_end is closest
     * to (latest.period_end - 1 year), within ±60 days. Returns a fraction (e.g. 0.12 = +12 %).
     */
    private function yoyDelta(Collection $readings, UtilityReading $latest): ?float
    {
        $target = $latest->period_end->copy()->subYear();
        $window = 60;

        $candidate = $readings
            ->filter(fn ($r) => $r->id !== $latest->id)
            ->filter(fn ($r) => abs($r->period_end->diffInDays($target, false)) <= $window)
            ->sortBy(fn ($r) => abs($r->period_end->diffInDays($target, false)))
            ->first();

        if (! $candidate || ! $candidate->consumption_value || (float) $candidate->consumption_value === 0.0) {
            return null;
        }

        $delta = ((float) $latest->consumption_value - (float) $candidate->consumption_value)
            / (float) $candidate->consumption_value;

        return round($delta, 4);
    }

    /**
     * Latest reading > ANOMALY_RATIO × trailing average of the prior ANOMALY_LOOKBACK readings.
     */
    private function isAnomaly(Collection $readings, UtilityReading $latest): bool
    {
        $prior = $readings
            ->filter(fn ($r) => $r->id !== $latest->id)
            ->sortByDesc('period_end')
            ->take(self::ANOMALY_LOOKBACK);

        if ($prior->count() < self::ANOMALY_LOOKBACK) {
            return false;
        }

        $avg = (float) $prior->avg('consumption_value');
        if ($avg <= 0) {
            return false;
        }

        return ((float) $latest->consumption_value) > self::ANOMALY_RATIO * $avg;
    }
}
