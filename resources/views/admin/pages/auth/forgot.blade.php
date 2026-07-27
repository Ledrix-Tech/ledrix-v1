@extends('admin.layout.auth')

@section('title', 'CRM Admin | Forgot Password')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="crm-auth-logo">
            <div class="crm-auth-dots"><span></span><span></span></div>
            <h1>Reset Password</h1>
            <p class="crm-auth-sub">Enter your email to receive a reset link</p>

            <form action="{{ route('admin.forgot.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <button type="submit" class="btn btn-submit">Send reset link</button>
            </form>

            <div class="crm-auth-links">
                <a href="{{ route('admin.login.get') }}">&larr; Back to login</a>
            </div>
        </div>
    </div>
@endsection
