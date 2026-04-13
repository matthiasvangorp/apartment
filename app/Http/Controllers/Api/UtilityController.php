<?php

namespace App\Http\Controllers\Api;

use App\Apartment\Analytics\UtilityAggregator;
use App\Apartment\Ingest\ClaudeExtractor;
use App\Http\Controllers\Controller;
use App\Models\UtilityReading;
use App\Models\UtilityStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UtilityController extends Controller
{
    public function summary(Request $request, UtilityAggregator $aggregator): JsonResponse
    {
        $type = $this->validatedType($request);

        $stat = UtilityStat::where('utility_type', $type)->orderByDesc('window_end')->first()
            ?? $aggregator->computeFor($type);

        if (! $stat) {
            return response()->json(['type' => $type, 'available' => false]);
        }

        $trend = UtilityReading::where('utility_type', $type)
            ->whereNotNull('consumption_value')
            ->whereNotNull('period_end')
            ->orderBy('period_end')
            ->get(['period_start', 'period_end', 'consumption_value', 'consumption_unit', 'amount_huf'])
            ->map(fn ($r) => [
                'period_start' => $r->period_start?->toDateString(),
                'period_end' => $r->period_end?->toDateString(),
                'consumption' => (float) $r->consumption_value,
                'unit' => $r->consumption_unit,
                'amount_huf' => $r->amount_huf !== null ? (float) $r->amount_huf : null,
            ])
            ->all();

        return response()->json([
            'type' => $type,
            'available' => true,
            'window_end' => $stat->window_end->toDateString(),
            'rolling_avg_12m' => $stat->rolling_avg_12m !== null ? (float) $stat->rolling_avg_12m : null,
            'last_value' => (float) $stat->last_value,
            'yoy_delta' => $stat->yoy_delta !== null ? (float) $stat->yoy_delta : null,
            'anomaly' => (bool) $stat->anomaly,
            'trend' => $trend,
        ]);
    }

    public function readings(Request $request): JsonResponse
    {
        $type = $this->validatedType($request);

        $query = UtilityReading::where('utility_type', $type)->orderBy('period_end');

        if ($from = $request->query('from')) {
            $query->where('period_end', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('period_end', '<=', $to);
        }

        return response()->json([
            'type' => $type,
            'readings' => $query->get()->map(fn ($r) => [
                'id' => $r->id,
                'document_id' => $r->document_id,
                'period_start' => $r->period_start?->toDateString(),
                'period_end' => $r->period_end?->toDateString(),
                'consumption' => $r->consumption_value !== null ? (float) $r->consumption_value : null,
                'unit' => $r->consumption_unit,
                'amount_huf' => $r->amount_huf !== null ? (float) $r->amount_huf : null,
                'meter_serial' => $r->meter_serial,
            ])->all(),
        ]);
    }

    private function validatedType(Request $request): string
    {
        $type = $request->query('type', 'electricity');
        abort_unless(in_array($type, ClaudeExtractor::UTILITY_TYPES, true), 422, 'invalid type');

        return $type;
    }
}
