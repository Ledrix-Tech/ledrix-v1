@extends('admin.layout.auth')

@section('title', 'CRM Admin | Login')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="crm-auth-logo">
            <div class="crm-auth-dots"><span></span><span></span></div>
            <h1>CRM Admin</h1>
            <p class="crm-auth-sub">Sign in to manage your workspace</p>

            @if (session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="alert alert-info" role="alert">{{ session('info') }}</div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <a href="{{ route('admin.forgot.get') }}">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-submit">Sign in</button>
            </form>
        </div>
    </div>
@endsection
