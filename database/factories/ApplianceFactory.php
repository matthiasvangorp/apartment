<?php

namespace Database\Factories;

use App\Models\Appliance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appliance>
 */
class ApplianceFactory extends Factory
{
    protected $model = Appliance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'brand' => $this->faker->company(),
            'model' => strtoupper($this->faker->bothify('??##-##')),
            'location' => $this->faker->randomElement(['bedroom', 'living room', 'kitchen', 'office', 'bathroom']),
            'purchased_on' => $this->faker->dateTimeBetween('-5 years')->format('Y-m-d'),
            'notes' => null,
            'manual_document_id' => null,
        ];
    }
}
