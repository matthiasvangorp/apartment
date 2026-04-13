<?php

namespace App\Console\Commands;

use App\Apartment\Ingest\IngestionPipeline;
use App\Jobs\IngestDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApartmentIngest extends Command
{
    protected $signature = 'apartment:ingest {--sync : Process inline instead of queueing}';

    protected $description = 'Scan the apartment inbox and ingest each PDF';

    public function handle(IngestionPipeline $pipeline): int
    {
        $inbox = config('apartment.paths.inbox');
        File::ensureDirectoryExists($inbox);

        $files = collect(File::files($inbox))
            ->filter(fn ($f) => strtolower($f->getExtension()) === 'pdf')
            ->values();

        if ($files->isEmpty()) {
            $this->info('Inbox empty.');

            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} PDF(s).");
        Log::channel('apartment')->info('ingest.batch_started', ['count' => $files->count()]);

        $tally = ['ingested' => 0, 'duplicate' => 0, 'failed' => 0];

        foreach ($files as $file) {
            $path = $file->getPathname();

            if (! $this->option('sync')) {
                dispatch(new IngestDocument($path));
                $this->line(' → '.basename($path).' (queued)');

                continue;
            }

            $result = $pipeline->process($path);
            $status = $result['status'];
            $tally[$status]++;

            $marker = match ($status) {
                'ingested' => '<fg=green>✓</>',
                'duplicate' => '<fg=yellow>⊙</>',
                'failed' => '<fg=red>✗</>',
            };
            $extra = $result['reason'] ?? '';
            $this->line(" {$marker} ".basename($path).($extra ? "  <fg=red>{$extra}</>" : ''));
        }

        if ($this->option('sync')) {
            $this->newLine();
            $this->info("ingested: {$tally['ingested']}  duplicate: {$tally['duplicate']}  failed: {$tally['failed']}");
        }

        return self::SUCCESS;
    }
}
