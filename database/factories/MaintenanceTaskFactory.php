<?php

namespace Database\Factories;

use App\Models\Appliance;
use App\Models\MaintenanceTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTask>
 */
class MaintenanceTaskFactory extends Factory
{
    protected $model = MaintenanceTask::class;

    public function definition(): array
    {
        return [
            'appliance_id' => Appliance::factory(),
            'title' => $this->faker->randomElement(['Filter cleaning', 'Descale', 'Grease filter wash', 'Annual service']),
            'cadence_months' => $this->faker->randomElement([3, 6, 12]),
            'last_done_on' => null,
            'next_due_on' => null,
            'notes' => null,
        ];
    }
}
