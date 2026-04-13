<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\UtilityReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UtilityReading>
 */
class UtilityReadingFactory extends Factory
{
    protected $model = UtilityReading::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $end = (clone $start)->modify('+1 month');

        return [
            'document_id' => Document::factory(),
            'utility_type' => $this->faker->randomElement(['electricity', 'water', 'gas', 'district_heating', 'internet']),
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'consumption_value' => $this->faker->randomFloat(3, 5, 500),
            'consumption_unit' => 'kWh',
            'amount_huf' => $this->faker->randomFloat(2, 1000, 100000),
            'meter_serial' => null,
        ];
    }
}
