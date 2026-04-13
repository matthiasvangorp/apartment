<?php

namespace App\Apartment\Ingest;

use App\Events\DocumentIngested;
use App\Models\Document;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngestionPipeline
{
    public function __construct(
        private readonly TextExtractor $textExtractor,
        private readonly ClaudeExtractor $claudeExtractor,
    ) {}

    /**
     * Process a single PDF from the inbox. Returns one of: ingested, duplicate, failed.
     *
     * @return array{status: 'ingested'|'duplicate'|'failed', document_id?: int, reason?: string}
     */
    public function process(string $absolutePath): array
    {
        $filename = basename($absolutePath);
        $log = ['file' => $filename];

        try {
            $extraction = $this->textExtractor->extract($absolutePath);
        } catch (\Throwable $e) {
            Log::channel('apartment')->error('ingest.failed_extract', $log + ['error' => $e->getMessage()]);

            return ['status' => 'failed', 'reason' => 'extract: '.$e->getMessage()];
        }

        $rawText = $extraction['text'];
        $sha = hash('sha256', $rawText);

        if ($existing = Document::where('raw_text_sha', $sha)->first()) {
            Log::channel('apartment')->info('ingest.duplicate_skipped', $log + ['document_id' => $existing->id]);
            @unlink($absolutePath);

            return ['status' => 'duplicate', 'document_id' => $existing->id];
        }

        try {
            $fields = $this->claudeExtractor->extract($rawText, $filename);
        } catch (\Throwable $e) {
            Log::channel('apartment')->error('ingest.failed_claude', $log + ['error' => $e->getMessage()]);

            return ['status' => 'failed', 'reason' => 'claude: '.$e->getMessage()];
        }

        $storagePath = $this->moveToKnowledge($absolutePath, $fields, $filename);

        $doc = DB::transaction(function () use ($fields, $rawText, $sha, $storagePath, $filename, $extraction) {
            $doc = Document::create([
                'category' => $fields['category'],
                'title_en' => $fields['title_en'],
                'summary_en' => $fields['summary_en'],
                'counterparty' => $fields['counterparty'],
                'issued_on' => $fields['issued_on'],
                'period_start' => $fields['period_start'],
                'period_end' => $fields['period_end'],
                'amount_huf' => $fields['amount_huf'],
                'currency' => $fields['currency'],
                'raw_text' => $rawText,
                'raw_text_sha' => $sha,
                'storage_path' => $storagePath,
                'original_filename' => $filename,
                'ingested_at' => now(),
                'meta' => ['extract_source' => $extraction['source']],
            ]);

            if ($fields['category'] === 'utility_invoice' && is_array($fields['utility'])) {
                UtilityReading::create([
                    'document_id' => $doc->id,
                    'utility_type' => $fields['utility']['utility_type'] ?? 'electricity',
                    'period_start' => $fields['period_start'],
                    'period_end' => $fields['period_end'],
                    'consumption_value' => $fields['utility']['consumption_value'] ?? null,
                    'consumption_unit' => $fields['utility']['consumption_unit'] ?? null,
                    'amount_huf' => $fields['amount_huf'],
                    'meter_serial' => $fields['utility']['meter_serial'] ?? null,
                ]);
            }

            return $doc;
        });

        Log::channel('apartment')->info('ingest.succeeded', $log + [
            'document_id' => $doc->id,
            'category' => $doc->category,
            'extract_source' => $extraction['source'],
        ]);

        DocumentIngested::dispatch($doc);

        return ['status' => 'ingested', 'document_id' => $doc->id];
    }

    private function moveToKnowledge(string $absolutePath, array $fields, string $filename): string
    {
        $category = $fields['category'];
        $monthStamp = Carbon::parse($fields['issued_on'] ?? now())->format('Y-m');
        $slug = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: Str::random(8);

        $relDir = "apartment/knowledge/{$category}/{$monthStamp}";
        $absDir = storage_path("app/{$relDir}");
        File::ensureDirectoryExists($absDir);

        $target = "{$absDir}/{$slug}.pdf";
        $i = 1;
        while (file_exists($target)) {
            $target = "{$absDir}/{$slug}-{$i}.pdf";
            $i++;
        }

        rename($absolutePath, $target);

        return "{$relDir}/".basename($target);
    }
}
