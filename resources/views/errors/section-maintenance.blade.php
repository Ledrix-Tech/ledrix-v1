<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance — {{ $section ?? 'Service' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #2563eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--accent);
            background: #eff6ff;
            padding: 6px 12px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
            line-height: 1.2;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 16px;
        }

        .message {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            background: #f1f5f9;
            color: var(--text);
            font-size: 15px;
        }

        .footer {
            margin-top: 28px;
            font-size: 13px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">503 · Maintenance</div>
        <h1>{{ $section ?? 'This area' }} is temporarily unavailable</h1>
        <p>We are performing scheduled maintenance. Please check back shortly.</p>

        @if (! empty($message))
            <div class="message">{{ $message }}</div>
        @endif

        <div class="footer">{{ config('app.name') }}</div>
    </div>
</body>
</html>
