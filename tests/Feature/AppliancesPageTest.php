<?php

namespace Tests\Feature;

use App\Livewire\Appliances;
use App\Models\Appliance;
use App\Models\MaintenanceTask;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppliancesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_appliances_with_tasks(): void
    {
        $appliance = Appliance::factory()->create(['name' => 'Test fridge']);
        MaintenanceTask::factory()->for($appliance)->create([
            'title' => 'Defrost',
            'cadence_months' => 6,
            'next_due_on' => '2026-09-01',
        ]);

        Livewire::test(Appliances::class)
            ->assertSee('Test fridge')
            ->assertSee('Defrost')
            ->assertSee('every 6 mo');
    }

    public function test_mark_done_updates_last_done_and_next_due(): void
    {
        Carbon::setTestNow('2026-04-13');

        $appliance = Appliance::factory()->create();
        $task = MaintenanceTask::factory()->for($appliance)->create([
            'cadence_months' => 6,
            'last_done_on' => null,
            'next_due_on' => '2026-05-01',
        ]);

        Livewire::test(Appliances::class)->call('markDone', $task->id);

        $task->refresh();
        $this->assertSame('2026-04-13', $task->last_done_on->toDateString());
        $this->assertSame('2026-10-13', $task->next_due_on->toDateString());

        Carbon::setTestNow();
    }
}
