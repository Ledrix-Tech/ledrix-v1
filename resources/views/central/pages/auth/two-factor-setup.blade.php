@extends('central.layout.auth')

@section('title', 'Enable 2FA')

@section('auth-content')
    <div class="card shadow-sm mx-auto" style="max-width: 480px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">Enable two-factor authentication</h1>
            <p class="text-muted small">Add this secret to Google Authenticator / Authy, then enter a code.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="mb-3">
                <label class="form-label">Secret</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $secret }}" onclick="this.select()">
            </div>
            <p class="small text-muted break-all">{{ $uri }}</p>

            <form method="POST" action="{{ route('super-admin.2fa.enable') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="code">6-digit code</label>
                    <input type="text" name="code" id="code" class="form-control" required autocomplete="one-time-code" inputmode="numeric">
                </div>
                <button type="submit" class="btn btn-primary w-100">Enable 2FA</button>
            </form>
        </div>
    </div>
@endsection
