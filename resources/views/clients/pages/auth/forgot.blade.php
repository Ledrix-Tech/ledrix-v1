@extends('clients.layout.auth')

@section('title', 'Forgot Password | Client Portal')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="crm-auth-logo">
            <div class="crm-auth-dots"><span></span><span></span></div>
            <h1>Reset password</h1>
            <p class="crm-auth-sub">Enter your email and we'll send you a reset link.</p>

            <form action="{{ route('client.forgot.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com"
                        required autofocus>
                </div>
                <div class="crm-auth-links mb-3">
                    <a href="{{ route('client.login.get') }}">Back to sign in</a>
                </div>
                <button type="submit" class="btn btn-submit">Send reset link</button>
            </form>
        </div>
    </div>
@endsection
