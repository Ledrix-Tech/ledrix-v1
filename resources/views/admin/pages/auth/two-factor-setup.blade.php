@extends('admin.layout.auth')

@section('title', 'Enable 2FA')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card" style="max-width: 480px;">
            <h1>Enable two-factor authentication</h1>
            <p class="crm-auth-sub">Add this secret to Google Authenticator / Authy, then enter a code.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="mb-3 text-start">
                <label class="form-label">Secret</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $secret }}" onclick="this.select()">
            </div>
            <p class="small text-muted text-break mb-3">{{ $uri }}</p>

            <form method="POST" action="{{ route('admin.2fa.enable') }}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label" for="code">6-digit code</label>
                    <input type="text" name="code" id="code" class="form-control" required autocomplete="one-time-code" inputmode="numeric">
                </div>
                <button type="submit" class="btn btn-submit">Enable 2FA</button>
            </form>
            <a href="{{ route('auth.profile.get') }}" class="d-inline-block mt-3 small">Back to profile</a>
        </div>
    </div>
@endsection
