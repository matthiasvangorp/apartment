<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appliance;
use Illuminate\Http\JsonResponse;

class ApplianceController extends Controller
{
    public function index(): JsonResponse
    {
        $appliances = Appliance::with(['maintenanceTasks' => fn ($q) => $q->orderBy('next_due_on')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'appliances' => $appliances->map(function (Appliance $a) {
                $lastDone = $a->maintenanceTasks->whereNotNull('last_done_on')->max('last_done_on');
                $nextDue = $a->maintenanceTasks->whereNotNull('next_due_on')->min('next_due_on');

                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'brand' => $a->brand,
                    'model' => $a->model,
                    'location' => $a->location,
                    'purchased_on' => $a->purchased_on?->toDateString(),
                    'manual_document_id' => $a->manual_document_id,
                    'last_maintenance_on' => $lastDone?->toDateString(),
                    'next_maintenance_due' => $nextDue?->toDateString(),
                    'tasks' => $a->maintenanceTasks->map(fn ($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'cadence_months' => $t->cadence_months,
                        'last_done_on' => $t->last_done_on?->toDateString(),
                        'next_due_on' => $t->next_due_on?->toDateString(),
                    ])->all(),
                ];
            })->all(),
        ]);
    }
}
