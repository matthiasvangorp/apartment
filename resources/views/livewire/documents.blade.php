<div>
    <h2 class="page-title">Documents</h2>

    <div class="panel">
        <div class="flex-row" style="margin-bottom: 16px;">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search title, summary, content…"
                style="flex: 1; min-width: 240px;"
            >
            <select wire:model.live="category">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ str_replace('_', ' ', $cat) }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $category !== '')
                <button type="button" class="btn secondary" wire:click="clearFilters">Clear</button>
            @endif
            <span class="muted" style="margin-left: auto; font-size: 0.85rem;">{{ $documents->total() }} result(s)</span>
        </div>

        @if ($documents->isEmpty())
            <div class="empty">No documents match these filters.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Counterparty</th>
                        <th class="right">Issued</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $doc)
                        <tr>
                            <td>
                                <a href="/documents/{{ $doc->id }}">{{ $doc->title_en ?? $doc->original_filename }}</a>
                                @if ($doc->summary_en)
                                    <div class="muted" style="font-size: 0.78rem; margin-top: 3px; line-height: 1.4;">
                                        {{ \Illuminate\Support\Str::limit($doc->summary_en, 140) }}
                                    </div>
                                @endif
                            </td>
                            <td><span class="badge cat-{{ $doc->category }}">{{ str_replace('_', ' ', $doc->category) }}</span></td>
                            <td class="muted">{{ $doc->counterparty ?? '—' }}</td>
                            <td class="right muted">{{ $doc->issued_on?->toDateString() ?? '—' }}</td>
                            <td class="right muted">
                                @if ($doc->amount_huf !== null)
                                    {{ number_format((float) $doc->amount_huf, 0, ',', ' ') }} {{ $doc->currency ?? 'HUF' }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 18px;">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
