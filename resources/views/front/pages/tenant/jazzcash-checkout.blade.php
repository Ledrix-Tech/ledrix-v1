<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to JazzCash…</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f7; color: #333; }
        .box { text-align: center; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 420px; }
        .spinner { width: 36px; height: 36px; border: 3px solid #e5e5e5; border-top-color: #4438c9; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <h2>Redirecting to JazzCash</h2>
        <p class="text-muted">Please wait while we connect you to the secure payment page…</p>
    </div>

    <form id="jazzcash-form" method="POST" action="{{ $checkoutUrl }}">
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('jazzcash-form').submit();
    </script>
</body>
</html>
