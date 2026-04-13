<?php

namespace Tests\Feature;

use App\Events\DocumentIngested;
use App\Models\Appliance;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkManualToApplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_manual_when_brand_and_model_match(): void
    {
        $appliance = Appliance::factory()->create([
            'name' => 'Ariston Velis Evo',
            'brand' => 'Ariston',
            'model' => 'Velis Evo',
            'manual_document_id' => null,
        ]);

        $doc = Document::factory()->create([
            'category' => 'appliance_manual',
            'title_en' => 'Ariston Velis Evo Water Heater Manual',
            'original_filename' => 'ariston-velis-evo.pdf',
        ]);

        DocumentIngested::dispatch($doc);

        $this->assertSame($doc->id, $appliance->fresh()->manual_document_id);
    }

    public function test_links_on_brand_only_when_model_is_null(): void
    {
        $appliance = Appliance::factory()->create([
            'name' => 'Govee bridge',
            'brand' => 'Govee',
            'model' => null,
            'manual_document_id' => null,
        ]);

        $doc = Document::factory()->create([
            'category' => 'appliance_manual',
            'title_en' => 'Govee Smart Bridge Setup',
            'original_filename' => 'govee.pdf',
        ]);

        DocumentIngested::dispatch($doc);

        $this->assertSame($doc->id, $appliance->fresh()->manual_document_id);
    }

    public function test_does_nothing_for_non_manual_categories(): void
    {
        $appliance = Appliance::factory()->create(['brand' => 'Bosch', 'model' => 'SMS46GI01E', 'manual_document_id' => null]);

        $doc = Document::factory()->create([
            'category' => 'utility_invoice',
            'title_en' => 'Bosch SMS46GI01E electricity bill',
        ]);

        DocumentIngested::dispatch($doc);

        $this->assertNull($appliance->fresh()->manual_document_id);
    }

    public function test_does_not_overwrite_an_already_linked_appliance(): void
    {
        $existingDoc = Document::factory()->create(['category' => 'appliance_manual']);
        $appliance = Appliance::factory()->create([
            'brand' => 'Bosch',
            'model' => 'SMS46',
            'manual_document_id' => $existingDoc->id,
        ]);

        $newDoc = Document::factory()->create([
            'category' => 'appliance_manual',
            'title_en' => 'Bosch SMS46 dishwasher manual',
        ]);

        DocumentIngested::dispatch($newDoc);

        $this->assertSame($existingDoc->id, $appliance->fresh()->manual_document_id);
    }
}
