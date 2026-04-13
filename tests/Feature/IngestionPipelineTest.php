<?php

namespace Tests\Feature;

use App\Apartment\Ingest\ClaudeExtractor;
use App\Apartment\Ingest\IngestionPipeline;
use App\Models\Document;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePdf = __DIR__.'/../fixtures/sample.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ClaudeExtractor::class, function ($mock) {
            $mock->shouldReceive('extract')->andReturn([
                'category' => 'utility_invoice',
                'title_en' => 'Test electricity bill',
                'summary_en' => 'Mock summary.',
                'issued_on' => '2025-06-01',
                'period_start' => '2025-05-01',
                'period_end' => '2025-05-31',
                'counterparty' => 'ELMŰ',
                'amount_huf' => 12345.67,
                'currency' => 'HUF',
                'utility' => [
                    'utility_type' => 'electricity',
                    'consumption_value' => 187.5,
                    'consumption_unit' => 'kWh',
                    'meter_serial' => 'SN-12345',
                ],
            ]);
        });
    }

    public function test_pipeline_ingests_pdf_and_creates_document_and_utility_reading(): void
    {
        if (! file_exists($this->fixturePdf)) {
            $this->markTestSkipped('Fixture PDF missing');
        }

        $inboxDir = storage_path('app/apartment/inbox');
        @mkdir($inboxDir, 0777, true);
        $target = $inboxDir.'/test-bill.pdf';
        copy($this->fixturePdf, $target);

        $pipeline = app(IngestionPipeline::class);
        $result = $pipeline->process($target);

        $this->assertSame('ingested', $result['status']);
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('utility_readings', 1);

        $doc = Document::first();
        $this->assertSame('utility_invoice', $doc->category);
        $this->assertSame('ELMŰ', $doc->counterparty);
        $this->assertFileExists(storage_path('app/'.$doc->storage_path));

        $reading = UtilityReading::first();
        $this->assertSame('electricity', $reading->utility_type);
        $this->assertEquals(187.500, (float) $reading->consumption_value);
    }

    public function test_pipeline_skips_duplicate_on_raw_text_sha(): void
    {
        if (! file_exists($this->fixturePdf)) {
            $this->markTestSkipped('Fixture PDF missing');
        }

        $inboxDir = storage_path('app/apartment/inbox');
        @mkdir($inboxDir, 0777, true);

        $first = $inboxDir.'/first.pdf';
        copy($this->fixturePdf, $first);
        app(IngestionPipeline::class)->process($first);
        $this->assertDatabaseCount('documents', 1);

        $second = $inboxDir.'/second.pdf';
        copy($this->fixturePdf, $second);
        $result = app(IngestionPipeline::class)->process($second);

        $this->assertSame('duplicate', $result['status']);
        $this->assertDatabaseCount('documents', 1);
    }
}
