<?php

namespace App\Livewire;

use App\Models\Appliance;
use App\Models\MaintenanceTask;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Appliances · Apartment')]
class Appliances extends Component
{
    public function markDone(int $taskId): void
    {
        $task = MaintenanceTask::findOrFail($taskId);
        $task->last_done_on = Carbon::today();
        $task->next_due_on = Carbon::today()->addMonths($task->cadence_months);
        $task->save();
    }

    public function render()
    {
        return view('livewire.appliances', [
            'appliances' => Appliance::with([
                'maintenanceTasks' => fn ($q) => $q->orderBy('next_due_on'),
                'manual:id,title_en',
            ])->orderBy('name')->get(),
        ]);
    }
}
