@extends('admin.layout.auth')

@section('title', 'Two-factor authentication')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card" style="max-width: 420px;">
            <h1>Authenticator code</h1>
            <p class="crm-auth-sub">Enter the code from your authenticator app or a recovery code.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.2fa.challenge.post') }}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label" for="code">Code</label>
                    <input type="text" name="code" id="code" class="form-control" required autofocus autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-submit">Verify</button>
            </form>
        </div>
    </div>
@endsection
