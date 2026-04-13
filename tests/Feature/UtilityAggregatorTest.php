<?php

namespace Tests\Feature;

use App\Apartment\Analytics\UtilityAggregator;
use App\Models\Document;
use App\Models\UtilityReading;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UtilityAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_anomaly_flags_when_latest_exceeds_1_3x_trailing_six_average(): void
    {
        $doc = Document::factory()->create();

        // 6 prior readings averaging 100 kWh
        $base = CarbonImmutable::parse('2025-01-31');
        foreach (range(0, 5) as $i) {
            UtilityReading::factory()->for($doc)->create([
                'utility_type' => 'electricity',
                'period_start' => $base->addMonths($i)->startOfMonth()->toDateString(),
                'period_end' => $base->addMonths($i)->endOfMonth()->toDateString(),
                'consumption_value' => 100,
                'consumption_unit' => 'kWh',
            ]);
        }

        // Latest reading: 150 kWh = 1.5x average → anomaly
        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_start' => $base->addMonths(6)->startOfMonth()->toDateString(),
            'period_end' => $base->addMonths(6)->endOfMonth()->toDateString(),
            'consumption_value' => 150,
            'consumption_unit' => 'kWh',
        ]);

        $stat = app(UtilityAggregator::class)->computeFor('electricity', CarbonImmutable::parse('2025-08-15'));

        $this->assertNotNull($stat);
        $this->assertTrue($stat->anomaly, 'Latest 150 vs avg 100 should be flagged as anomaly');
        $this->assertEquals(150.0, (float) $stat->last_value);
    }

    public function test_no_anomaly_when_latest_within_threshold(): void
    {
        $doc = Document::factory()->create();
        $base = CarbonImmutable::parse('2025-01-31');

        foreach (range(0, 5) as $i) {
            UtilityReading::factory()->for($doc)->create([
                'utility_type' => 'electricity',
                'period_start' => $base->addMonths($i)->startOfMonth()->toDateString(),
                'period_end' => $base->addMonths($i)->endOfMonth()->toDateString(),
                'consumption_value' => 100,
                'consumption_unit' => 'kWh',
            ]);
        }

        // Latest: 125 kWh = 1.25x average → NOT anomaly
        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_start' => $base->addMonths(6)->startOfMonth()->toDateString(),
            'period_end' => $base->addMonths(6)->endOfMonth()->toDateString(),
            'consumption_value' => 125,
        ]);

        $stat = app(UtilityAggregator::class)->computeFor('electricity', CarbonImmutable::parse('2025-08-15'));

        $this->assertFalse($stat->anomaly);
    }

    public function test_no_anomaly_when_history_too_short(): void
    {
        $doc = Document::factory()->create();
        UtilityReading::factory()->for($doc)->count(2)->sequence(
            ['period_end' => '2025-01-31', 'consumption_value' => 100],
            ['period_end' => '2025-02-28', 'consumption_value' => 500],
        )->create(['utility_type' => 'electricity']);

        $stat = app(UtilityAggregator::class)->computeFor('electricity', CarbonImmutable::parse('2025-03-15'));

        $this->assertFalse($stat->anomaly, 'Need ≥6 prior readings to flag anomalies');
    }

    public function test_yoy_delta_compares_to_reading_one_year_prior(): void
    {
        $doc = Document::factory()->create();

        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_start' => '2024-06-01',
            'period_end' => '2024-06-30',
            'consumption_value' => 200,
        ]);
        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_start' => '2025-06-01',
            'period_end' => '2025-06-30',
            'consumption_value' => 240,
        ]);

        $stat = app(UtilityAggregator::class)->computeFor('electricity', CarbonImmutable::parse('2025-07-01'));

        // (240 - 200) / 200 = 0.20
        $this->assertEquals(0.20, (float) $stat->yoy_delta);
    }

    public function test_recompute_is_idempotent_on_unique_key(): void
    {
        $doc = Document::factory()->create();
        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_end' => '2025-06-30',
            'consumption_value' => 150,
        ]);

        $agg = app(UtilityAggregator::class);
        $agg->recomputeAll();
        $agg->recomputeAll();

        $this->assertSame(1, \App\Models\UtilityStat::count());
    }
}
