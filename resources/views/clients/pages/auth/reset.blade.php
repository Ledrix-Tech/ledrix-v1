@extends('clients.layout.auth')

@section('title', 'Reset Password | Client Portal')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="crm-auth-logo">
            <div class="crm-auth-dots"><span></span><span></span></div>
            <h1>Set new password</h1>
            <p class="crm-auth-sub">Choose a strong password for your account.</p>

            <form action="{{ route('client.reset.post') }}" method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cpassword">Confirm password</label>
                    <input type="password" id="cpassword" name="cpassword" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-submit">Update password</button>
            </form>
        </div>
    </div>
@endsection
