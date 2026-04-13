<?php

namespace App\Jobs;

use App\Models\MaintenanceTask;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecomputeMaintenance implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function handle(): void
    {
        $count = 0;
        foreach (MaintenanceTask::query()->cursor() as $task) {
            $next = $task->last_done_on
                ? Carbon::parse($task->last_done_on)->addMonths($task->cadence_months)->toDateString()
                : Carbon::today()->addMonths($task->cadence_months)->toDateString();

            if ((string) $task->next_due_on?->toDateString() !== $next) {
                $task->next_due_on = $next;
                $task->save();
                $count++;
            }
        }

        Log::channel('apartment')->info('maintenance.recomputed', ['updated' => $count]);
    }
}
