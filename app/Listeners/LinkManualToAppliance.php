<?php

namespace App\Listeners;

use App\Events\DocumentIngested;
use App\Models\Appliance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkManualToAppliance
{
    public function handle(DocumentIngested $event): void
    {
        $doc = $event->document;
        if ($doc->category !== 'appliance_manual') {
            return;
        }

        $haystack = Str::lower(trim(implode(' ', [
            $doc->title_en ?? '',
            $doc->summary_en ?? '',
            $doc->original_filename ?? '',
        ])));

        $match = Appliance::query()
            ->whereNull('manual_document_id')
            ->get()
            ->first(function (Appliance $a) use ($haystack) {
                $brand = $a->brand ? Str::lower($a->brand) : null;
                $model = $a->model ? Str::lower($a->model) : null;

                if ($brand && $model) {
                    return str_contains($haystack, $brand) && str_contains($haystack, $model);
                }
                if ($brand) {
                    return str_contains($haystack, $brand);
                }

                return false;
            });

        if (! $match) {
            Log::channel('apartment')->info('maintenance.manual_unlinked', [
                'document_id' => $doc->id,
                'title' => $doc->title_en,
            ]);

            return;
        }

        $match->manual_document_id = $doc->id;
        $match->save();

        Log::channel('apartment')->info('maintenance.manual_linked', [
            'document_id' => $doc->id,
            'appliance_id' => $match->id,
            'appliance' => $match->name,
        ]);
    }
}
