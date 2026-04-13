<div>
    <h2 class="page-title">Overview</h2>

    <div class="metrics">
        <div class="metric-card">
            <div class="label">Documents</div>
            <div class="value">{{ $metrics['documents'] }}</div>
            <div class="sub">ingested into the knowledge base</div>
        </div>

        <div class="metric-card green">
            <div class="label">Electricity — last bill</div>
            <div class="value">
                @if ($metrics['electricity_last'] !== null)
                    {{ number_format($metrics['electricity_last'], 0, ',', ' ') }} <span class="muted" style="font-size:0.7em">kWh</span>
                @else
                    —
                @endif
            </div>
            <div class="sub">
                @if ($metrics['electricity_avg'] !== null)
                    12-month avg {{ number_format($metrics['electricity_avg'], 0, ',', ' ') }} kWh
                @else
                    no readings yet
                @endif
            </div>
        </div>

        <div class="metric-card {{ $metrics['electricity_anomaly'] ? 'red' : 'teal' }}">
            <div class="label">Electricity anomaly</div>
            <div class="value">{{ $metrics['electricity_anomaly'] ? 'YES' : 'No' }}</div>
            <div class="sub">latest > 1.3× trailing 6-bill avg</div>
        </div>

        <div class="metric-card orange">
            <div class="label">Maintenance — next 90 days</div>
            <div class="value">{{ $metrics['tasks_due'] }}</div>
            <div class="sub">tasks due</div>
        </div>

        <div class="metric-card purple">
            <div class="label">Appliances tracked</div>
            <div class="value">{{ $metrics['appliances'] }}</div>
            <div class="sub">with maintenance schedules</div>
        </div>
    </div>

    <div class="panel">
        <h3 class="panel-title">Electricity consumption</h3>
        @if (count($trend) > 0)
            <canvas id="electricityChart" height="80"></canvas>
        @else
            <div class="empty">No electricity readings yet — drop a bill into <code>storage/app/apartment/inbox/</code>.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">Upcoming maintenance</h3>
        @if ($upcoming->isEmpty())
            <div class="empty">Nothing scheduled.</div>
        @else
            <table class="data">
                <thead>
                    <tr><th>Appliance</th><th>Task</th><th>Cadence</th><th class="right">Next due</th></tr>
                </thead>
                <tbody>
                    @foreach ($upcoming as $task)
                        <tr>
                            <td>{{ $task->appliance?->name ?? '—' }} <span class="muted">· {{ $task->appliance?->location }}</span></td>
                            <td>{{ $task->title }}</td>
                            <td class="muted">every {{ $task->cadence_months }} mo</td>
                            <td class="right">{{ $task->next_due_on?->toDateString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">Recently ingested</h3>
        @if ($recent->isEmpty())
            <div class="empty">No documents yet.</div>
        @else
            <table class="data">
                <thead>
                    <tr><th>Title</th><th>Category</th><th>Counterparty</th><th class="right">Issued</th></tr>
                </thead>
                <tbody>
                    @foreach ($recent as $doc)
                        <tr>
                            <td><a href="/documents/{{ $doc->id }}">{{ $doc->title_en ?? $doc->original_filename }}</a></td>
                            <td><span class="badge cat-{{ $doc->category }}">{{ str_replace('_', ' ', $doc->category) }}</span></td>
                            <td class="muted">{{ $doc->counterparty ?? '—' }}</td>
                            <td class="right muted">{{ $doc->issued_on?->toDateString() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if (count($trend) > 0)
        <script>
            (function() {
                const data = @json($trend);
                const ctx = document.getElementById('electricityChart').getContext('2d');
                const labels = data.map(p => p.period_end);
                const consumption = data.map(p => p.consumption);

                function colors() {
                    const dark = document.body.classList.contains('dark-mode');
                    return {
                        line: '#3b82f6',
                        fill: 'rgba(59, 130, 246, 0.15)',
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
                            datasets: [{
                                label: 'kWh',
                                data: consumption,
                                borderColor: c.line,
                                backgroundColor: c.fill,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 4,
                                pointBackgroundColor: c.line,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' kWh' } }
                            },
                            scales: {
                                x: { ticks: { color: c.text }, grid: { color: c.grid } },
                                y: { ticks: { color: c.text }, grid: { color: c.grid }, beginAtZero: true }
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
