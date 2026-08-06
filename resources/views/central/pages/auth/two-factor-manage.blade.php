@extends('central.layout.auth')

@section('title', '2FA enabled')

@section('auth-content')
    <div class="card shadow-sm mx-auto" style="max-width: 420px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">Two-factor authentication</h1>
            <p class="text-muted small">2FA is enabled on your Super Admin account.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('super-admin.2fa.disable') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="code">Authenticator or recovery code</label>
                    <input type="text" name="code" id="code" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-outline-danger w-100">Disable 2FA</button>
            </form>

            <a href="{{ route('super-admin.index.get') }}" class="btn btn-link w-100 mt-2">Back to dashboard</a>
        </div>
    </div>
@endsection
