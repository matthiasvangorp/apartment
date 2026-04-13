<div wire:key="utility-{{ $type }}">
    <div class="flex-row" style="margin-bottom: 20px; align-items: baseline;">
        <h2 class="page-title" style="margin: 0;">Utility</h2>
        @if (count($availableTypes) > 1)
            <select wire:model.live="type">
                @foreach ($availableTypes as $t)
                    <option value="{{ $t }}">{{ str_replace('_', ' ', $t) }}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if ($stat)
        <div class="metrics">
            <div class="metric-card green">
                <div class="label">Last reading</div>
                <div class="value">{{ number_format((float) $stat->last_value, 0, ',', ' ') }} <span class="muted" style="font-size:0.7em">kWh</span></div>
                <div class="sub">window ends {{ $stat->window_end->toDateString() }}</div>
            </div>
            <div class="metric-card">
                <div class="label">12-month rolling avg</div>
                <div class="value">
                    @if ($stat->rolling_avg_12m !== null)
                        {{ number_format((float) $stat->rolling_avg_12m, 0, ',', ' ') }} <span class="muted" style="font-size:0.7em">kWh</span>
                    @else — @endif
                </div>
                <div class="sub">across all readings within the window</div>
            </div>
            <div class="metric-card purple">
                <div class="label">YoY delta</div>
                <div class="value">
                    @if ($stat->yoy_delta !== null)
                        {{ ($stat->yoy_delta >= 0 ? '+' : '') }}{{ number_format(((float) $stat->yoy_delta) * 100, 1) }}%
                    @else
                        —
                    @endif
                </div>
                <div class="sub">vs same period last year</div>
            </div>
            <div class="metric-card {{ $stat->anomaly ? 'red' : 'teal' }}">
                <div class="label">Anomaly</div>
                <div class="value">{{ $stat->anomaly ? 'YES' : 'No' }}</div>
                <div class="sub">latest > 1.3× trailing 6-bill avg</div>
            </div>
        </div>
    @endif

    <div class="panel">
        <h3 class="panel-title">Consumption & cost</h3>
        @if (count($chartData) > 0)
            <canvas id="utilityChart" height="80"></canvas>
        @else
            <div class="empty">No readings for {{ $type }} yet.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">All readings</h3>
        @if ($readings->isEmpty())
            <div class="empty">No readings yet.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="right">Consumption</th>
                        <th class="right">Amount</th>
                        <th>Meter</th>
                        <th>Document</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($readings->reverse() as $r)
                        <tr>
                            <td>{{ $r->period_start?->toDateString() ?? '?' }} → {{ $r->period_end?->toDateString() ?? '?' }}</td>
                            <td class="right">{{ number_format((float) $r->consumption_value, 0, ',', ' ') }} {{ $r->consumption_unit }}</td>
                            <td class="right muted">
                                @if ($r->amount_huf !== null)
                                    {{ number_format((float) $r->amount_huf, 0, ',', ' ') }} HUF
                                @else — @endif
                            </td>
                            <td class="muted">{{ $r->meter_serial ?? '—' }}</td>
                            <td><a href="/documents/{{ $r->document_id }}">#{{ $r->document_id }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if (count($chartData) > 0)
        <script>
            (function() {
                const data = @json($chartData);
                const ctx = document.getElementById('utilityChart').getContext('2d');
                const labels = data.map(p => p.period_end);
                const consumption = data.map(p => p.consumption);
                const cost = data.map(p => p.amount_huf);

                function colors() {
                    const dark = document.body.classList.contains('dark-mode');
                    return {
                        line1: '#3b82f6',
                        fill1: 'rgba(59, 130, 246, 0.15)',
                        line2: '#f59e0b',
                        text: dark ? '#9ca3af' : '#6b7280',
                        grid: dark ? '#374151' : '#e5e7eb',
                    };
                }

                let chart;
                function draw() {
                    if (chart) chart.destroy();
                    const c = colors();
                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'kWh',
                                    data: consumption,
                                    yAxisID: 'y',
                                    borderColor: c.line1,
                                    backgroundColor: c.fill1,
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 4,
                                    pointBackgroundColor: c.line1,
                                },
                                {
                                    label: 'HUF',
                                    data: cost,
                                    yAxisID: 'y2',
                                    borderColor: c.line2,
                                    backgroundColor: 'transparent',
                                    borderDash: [5, 5],
                                    fill: false,
                                    tension: 0.3,
                                    pointRadius: 3,
                                    pointBackgroundColor: c.line2,
                                },
                            ]
                        },
                        options: {
                            responsive: true,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { labels: { color: c.text } },
                            },
                            scales: {
                                x: { ticks: { color: c.text }, grid: { color: c.grid } },
                                y: {
                                    position: 'left',
                                    ticks: { color: c.text }, grid: { color: c.grid },
                                    title: { display: true, text: 'kWh', color: c.text },
                                    beginAtZero: true,
                                },
                                y2: {
                                    position: 'right',
                                    ticks: { color: c.text }, grid: { drawOnChartArea: false },
                                    title: { display: true, text: 'HUF', color: c.text },
                                    beginAtZero: true,
                                },
                            }
                        }
                    });
                }
                draw();
                window.addEventListener('apartment-theme-changed', draw);
            })();
        </script>
    @endif
</div>
