<?php

namespace Database\Seeders;

use App\Models\Appliance;
use App\Models\MaintenanceTask;
use Illuminate\Database\Seeder;

class ApplianceSeeder extends Seeder
{
    public function run(): void
    {
        $appliances = [
            ['name' => 'Gree AC — Bedroom', 'brand' => 'Gree', 'model' => null, 'location' => 'bedroom',
                'tasks' => [['title' => 'Filter cleaning', 'cadence_months' => 12]]],
            ['name' => 'Gree AC — Living room', 'brand' => 'Gree', 'model' => null, 'location' => 'living room',
                'tasks' => [['title' => 'Filter cleaning', 'cadence_months' => 12]]],

            ['name' => 'Ariston Velis Evo water heater', 'brand' => 'Ariston', 'model' => 'Velis Evo', 'location' => 'bathroom',
                'tasks' => [['title' => 'Descaling check', 'cadence_months' => 6]]],

            ['name' => 'AEG/Electrolux dishwasher', 'brand' => 'AEG', 'model' => 'EEM48320L', 'location' => 'kitchen',
                'tasks' => [['title' => 'Salt / rinse aid check', 'cadence_months' => 12]]],

            ['name' => 'AEG induction hob with downdraft', 'brand' => 'AEG', 'model' => 'CCE84543FB', 'location' => 'kitchen',
                'tasks' => [['title' => 'Grease filter wash', 'cadence_months' => 3]]],

            ['name' => 'AEG pyrolytic oven', 'brand' => 'AEG', 'model' => 'BPE748380B', 'location' => 'kitchen',
                'tasks' => [['title' => 'Pyrolytic self-clean cycle', 'cadence_months' => 3]]],

            ['name' => 'Gorenje fridge-freezer', 'brand' => 'Gorenje', 'model' => 'ONRK619DBK', 'location' => 'kitchen',
                'tasks' => [['title' => 'Coil cleaning + door seal check', 'cadence_months' => 12]]],

            ['name' => 'Dyson Cyclone V10', 'brand' => 'Dyson', 'model' => 'Cyclone V10', 'location' => 'pantry',
                'tasks' => [['title' => 'Wash filter (cold water, dry 24h)', 'cadence_months' => 1]]],
        ];

        foreach ($appliances as $spec) {
            $tasks = $spec['tasks'];
            unset($spec['tasks']);

            $appliance = Appliance::firstOrCreate(
                ['name' => $spec['name']],
                $spec,
            );

            foreach ($tasks as $task) {
                MaintenanceTask::firstOrCreate(
                    ['appliance_id' => $appliance->id, 'title' => $task['title']],
                    $task,
                );
            }
        }
    }
}
