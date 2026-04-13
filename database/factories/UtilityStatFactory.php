<?php

namespace Database\Factories;

use App\Models\UtilityStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UtilityStat>
 */
class UtilityStatFactory extends Factory
{
    protected $model = UtilityStat::class;

    public function definition(): array
    {
        return [
            'utility_type' => $this->faker->randomElement(['electricity', 'water', 'gas']),
            'window_end' => now()->toDateString(),
            'rolling_avg_12m' => $this->faker->randomFloat(3, 50, 300),
            'last_value' => $this->faker->randomFloat(3, 50, 300),
            'yoy_delta' => $this->faker->randomFloat(4, -0.5, 0.5),
            'anomaly' => false,
            'computed_at' => now(),
        ];
    }
}
