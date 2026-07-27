@extends('central.layout.auth')

@section('title', 'Super Admin | Reset Password')

@section('auth-content')
    <div class="sa-auth-page">
        <div class="sa-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="sa-auth-logo">
            <h1>New Password</h1>
            <p class="sa-auth-sub">Choose a new password for your account</p>

            <form action="{{ route('super-admin.reset.post') }}" method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cpassword">Confirm password</label>
                    <input type="password" id="cpassword" name="cpassword" class="form-control" placeholder="Confirm password" required>
                </div>
                <button type="submit" class="btn btn-submit">Reset password</button>
            </form>

            <div class="sa-auth-links">
                <a href="{{ route('super-admin.login.get') }}">&larr; Back to login</a>
            </div>
        </div>
    </div>
@endsection
