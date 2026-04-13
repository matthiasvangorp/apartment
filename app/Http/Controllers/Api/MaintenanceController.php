<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function upcoming(Request $request): JsonResponse
    {
        $within = (int) $request->query('within_days', 90);
        $cutoff = now()->addDays($within)->toDateString();

        $tasks = MaintenanceTask::query()
            ->whereNotNull('next_due_on')
            ->where('next_due_on', '<=', $cutoff)
            ->with('appliance:id,name,location')
            ->orderBy('next_due_on')
            ->get();

        return response()->json([
            'within_days' => $within,
            'tasks' => $tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'cadence_months' => $t->cadence_months,
                'last_done_on' => $t->last_done_on?->toDateString(),
                'next_due_on' => $t->next_due_on?->toDateString(),
                'appliance' => $t->appliance ? [
                    'id' => $t->appliance->id,
                    'name' => $t->appliance->name,
                    'location' => $t->appliance->location,
                ] : null,
            ])->all(),
        ]);
    }
}
