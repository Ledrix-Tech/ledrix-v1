@extends('admin.layout.auth')

@section('title', '2FA enabled')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card" style="max-width: 420px;">
            <h1>Two-factor authentication</h1>
            <p class="crm-auth-sub">2FA is enabled on your CRM admin account.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.2fa.disable') }}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label" for="code">Authenticator or recovery code</label>
                    <input type="text" name="code" id="code" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-outline-danger w-100">Disable 2FA</button>
            </form>

            <a href="{{ route('auth.profile.get') }}" class="d-inline-block mt-3 small">Back to profile</a>
        </div>
    </div>
@endsection
