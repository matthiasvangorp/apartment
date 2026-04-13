<div>
    <div class="flex-row" style="margin-bottom: 20px;">
        <a href="/documents" class="muted">← All documents</a>
    </div>

    <h2 class="page-title" style="margin-bottom: 8px;">{{ $document->title_en ?? $document->original_filename }}</h2>
    <div class="flex-row" style="margin-bottom: 24px;">
        <span class="badge cat-{{ $document->category }}">{{ str_replace('_', ' ', $document->category) }}</span>
        @if ($document->counterparty)
            <span class="muted">· {{ $document->counterparty }}</span>
        @endif
        @if ($document->issued_on)
            <span class="muted">· issued {{ $document->issued_on->toDateString() }}</span>
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">Summary</h3>
        @if ($document->summary_en)
            <p style="line-height: 1.6;">{{ $document->summary_en }}</p>
        @else
            <div class="empty">No summary available.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">Details</h3>
        <table class="data">
            <tbody>
                <tr><td class="muted" style="width: 200px;">Original filename</td><td>{{ $document->original_filename }}</td></tr>
                <tr><td class="muted">Storage path</td><td><code>{{ $document->storage_path }}</code></td></tr>
                <tr><td class="muted">Ingested at</td><td>{{ $document->ingested_at?->toDateTimeString() ?? '—' }}</td></tr>
                @if ($document->period_start || $document->period_end)
                    <tr><td class="muted">Period</td><td>{{ $document->period_start?->toDateString() ?? '?' }} → {{ $document->period_end?->toDateString() ?? '?' }}</td></tr>
                @endif
                @if ($document->amount_huf !== null)
                    <tr><td class="muted">Amount</td><td>{{ number_format((float) $document->amount_huf, 2, ',', ' ') }} {{ $document->currency ?? 'HUF' }}</td></tr>
                @endif
                @if ($reading)
                    <tr><td class="muted">Utility reading</td><td>{{ $reading->utility_type }} · {{ $reading->consumption_value }} {{ $reading->consumption_unit }}@if ($reading->meter_serial) · meter {{ $reading->meter_serial }}@endif</td></tr>
                @endif
                @if ($linkedAppliance)
                    <tr><td class="muted">Linked appliance</td><td><a href="/appliances">{{ $linkedAppliance->name }}</a></td></tr>
                @endif
            </tbody>
        </table>
        <div style="margin-top: 18px;">
            <a class="btn" href="{{ $downloadUrl }}" target="_blank" rel="noopener">Open PDF</a>
        </div>
    </div>
</div>
