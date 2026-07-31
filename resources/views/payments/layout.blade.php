<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Secure Payment') — {{ $brand->brand_name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ url('front-assets/imgs/favicon-32.png') }}" rel="icon">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18);
            --radius: 16px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, #1e293b 0%, var(--bg) 55%);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .pay-shell {
            width: 100%;
            max-width: 520px;
        }

        .pay-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #fff;
        }

        .pay-brand img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #fff;
            object-fit: contain;
            padding: 6px;
        }

        .pay-brand span {
            font-weight: 600;
            font-size: 1rem;
            opacity: 0.95;
        }

        .pay-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .pay-card-header {
            padding: 28px 28px 0;
            text-align: center;
        }

        .pay-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 16px;
        }

        .pay-icon--success { background: #d1fae5; color: var(--success); }
        .pay-icon--warning { background: #fef3c7; color: var(--warning); }
        .pay-icon--danger  { background: #fee2e2; color: var(--danger); }
        .pay-icon--info    { background: #e0f2fe; color: var(--primary); }

        .pay-card-header h1 {
            margin: 0 0 8px;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .pay-card-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .pay-card-body {
            padding: 24px 28px 28px;
        }

        .pay-summary {
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .pay-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .pay-summary-row:last-child { border-bottom: none; }

        .pay-summary-row span:first-child {
            color: var(--text-muted);
            font-weight: 500;
        }

        .pay-summary-row span:last-child {
            font-weight: 600;
            text-align: right;
        }

        .pay-amount-highlight {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            color: var(--primary-dark);
        }

        .pay-field {
            margin-bottom: 14px;
        }

        .pay-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .pay-field input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            background: var(--surface-muted);
            color: var(--text);
        }

        .pay-field input:read-only {
            cursor: default;
        }

        .pay-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 480px) {
            .pay-grid { grid-template-columns: 1fr; }
        }

        .pay-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .pay-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.25);
        }

        .pay-btn--primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
        }

        .pay-btn--stripe {
            background: linear-gradient(135deg, #635bff 0%, #4f46e5 100%);
            color: #fff;
        }

        .pay-btn--paypal {
            background: linear-gradient(135deg, #0070ba 0%, #003087 100%);
            color: #fff;
        }

        .pay-btn--ghost {
            background: var(--surface-muted);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .pay-btn--ghost:hover {
            box-shadow: none;
            background: #f1f5f9;
        }

        .pay-secure {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pay-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
        }

        .pay-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $brandName = $brand->brand_name ?? config('app.name');
        $brandUrl  = trim((string) ($brand->brand_url ?? ''));
        $host      = $brandUrl ? parse_url(str_starts_with($brandUrl, 'http') ? $brandUrl : 'https://' . $brandUrl, PHP_URL_HOST) : null;
        $brandIcon = $host ? "https://www.google.com/s2/favicons?sz=64&domain={$host}" : url('admin-assets/dpm-logos/dpm-fav.png');
    @endphp

    <div class="pay-shell">
        @isset($brand)
        <div class="pay-brand">
            <img src="{{ $brandIcon }}" alt="{{ $brandName }}" onerror="this.src='{{ url('admin-assets/dpm-logos/dpm-fav.png') }}'">
            <span>{{ $brandName }}</span>
        </div>
        @endisset

        <div class="pay-card">
            @yield('content')
        </div>

        <div class="pay-footer">Secure payment powered by {{ config('app.name') }}</div>
    </div>

    @stack('scripts')
</body>
</html>
