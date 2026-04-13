<?php

namespace App\Http\Controllers\Api;

use App\Apartment\Ingest\ClaudeExtractor;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DocumentController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $limit = min(max((int) $request->query('limit', 20), 1), 100);

        if ($category !== null && ! in_array($category, ClaudeExtractor::CATEGORIES, true)) {
            abort(422, 'invalid category');
        }

        $query = Document::query();

        if ($category) {
            $query->where('category', $category);
        }

        if ($q !== '') {
            if (config('database.default') === 'mysql') {
                $query->whereRaw('MATCH(title_en, summary_en, raw_text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q])
                    ->orderByRaw('MATCH(title_en, summary_en, raw_text) AGAINST (? IN NATURAL LANGUAGE MODE) DESC', [$q]);
            } else {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(function ($w) use ($like) {
                    $w->where('title_en', 'like', $like)
                        ->orWhere('summary_en', 'like', $like)
                        ->orWhere('raw_text', 'like', $like)
                        ->orWhere('original_filename', 'like', $like);
                });
            }
        } else {
            $query->orderByDesc('issued_on')->orderByDesc('id');
        }

        return response()->json([
            'query' => $q,
            'category' => $category,
            'results' => $query->limit($limit)
                ->get(['id', 'category', 'title_en', 'summary_en', 'counterparty', 'issued_on', 'amount_huf', 'currency', 'original_filename'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'category' => $d->category,
                    'title_en' => $d->title_en,
                    'summary_en' => $d->summary_en,
                    'counterparty' => $d->counterparty,
                    'issued_on' => $d->issued_on?->toDateString(),
                    'amount_huf' => $d->amount_huf !== null ? (float) $d->amount_huf : null,
                    'currency' => $d->currency,
                    'original_filename' => $d->original_filename,
                ])->all(),
        ]);
    }

    public function show(Document $document): JsonResponse
    {
        $signedUrl = URL::temporarySignedRoute(
            'apartment.documents.download',
            now()->addMinutes(30),
            ['document' => $document->id],
        );

        return response()->json([
            'id' => $document->id,
            'category' => $document->category,
            'title_en' => $document->title_en,
            'summary_en' => $document->summary_en,
            'counterparty' => $document->counterparty,
            'issued_on' => $document->issued_on?->toDateString(),
            'period_start' => $document->period_start?->toDateString(),
            'period_end' => $document->period_end?->toDateString(),
            'amount_huf' => $document->amount_huf !== null ? (float) $document->amount_huf : null,
            'currency' => $document->currency,
            'original_filename' => $document->original_filename,
            'storage_path' => $document->storage_path,
            'download_url' => $signedUrl,
            'download_expires_at' => now()->addMinutes(30)->toIso8601String(),
        ]);
    }

    public function download(Request $request, Document $document)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $absolute = storage_path('app/'.$document->storage_path);
        abort_unless(file_exists($absolute), 404);

        return response()->file($absolute, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($document->original_filename).'"',
        ]);
    }
}
