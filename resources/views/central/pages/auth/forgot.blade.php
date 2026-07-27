@extends('central.layout.auth')

@section('title', 'Super Admin | Forgot Password')

@section('auth-content')
    <div class="sa-auth-page">
        <div class="sa-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="sa-auth-logo">
            <h1>Reset Password</h1>
            <p class="sa-auth-sub">Enter your email to receive a reset link</p>

            <form action="{{ route('super-admin.forgot.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <button type="submit" class="btn btn-submit">Send reset link</button>
            </form>

            <div class="sa-auth-links">
                <a href="{{ route('super-admin.login.get') }}">&larr; Back to login</a>
            </div>
        </div>
    </div>
@endsection
