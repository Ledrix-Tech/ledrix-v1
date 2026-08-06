@extends('central.layout.auth')

@section('title', 'Two-factor authentication')

@section('auth-content')
    <div class="card shadow-sm mx-auto" style="max-width: 420px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">Authenticator code</h1>
            <p class="text-muted small">Enter the code from your authenticator app or a recovery code.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('super-admin.2fa.challenge.post') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="code">Code</label>
                    <input type="text" name="code" id="code" class="form-control" required autofocus autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
        </div>
    </div>
@endsection
