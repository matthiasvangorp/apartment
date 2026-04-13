<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Apartment' }}</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    @livewireStyles
</head>
<body>
    <button type="button" class="dark-toggle" onclick="toggleDark()" aria-label="Toggle dark mode">🌓</button>

    <div class="app-shell">
        <aside class="sidebar">
            <h1>🏠 Apartment</h1>
            <nav>
                <a href="/" @class(['active' => request()->is('/')])>Overview</a>
                <a href="/documents" @class(['active' => request()->is('documents*')])>Documents</a>
                <a href="/appliances" @class(['active' => request()->is('appliances*')])>Appliances</a>
                <a href="/utility" @class(['active' => request()->is('utility*')])>Utility</a>
            </nav>
        </aside>
        <main class="main">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    <script>
        (function() {
            const stored = localStorage.getItem('apartment-dark');
            if (stored === '1') document.body.classList.add('dark-mode');
        })();
        function toggleDark() {
            const on = document.body.classList.toggle('dark-mode');
            localStorage.setItem('apartment-dark', on ? '1' : '0');
            window.dispatchEvent(new CustomEvent('apartment-theme-changed', { detail: { dark: on } }));
        }
    </script>
</body>
</html>
