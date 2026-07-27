@extends('sellers.layout.auth')

@section('title', 'Seller Portal | Login')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="crm-auth-logo">
            <div class="crm-auth-dots"><span></span><span></span></div>
            <h1>Seller Portal</h1>
            <p class="crm-auth-sub">Sign in to your sales workspace</p>

            <form action="{{ route('seller.login.post') }}" method="post">
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
                    <a href="{{ route('seller.forgot.get') }}">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-submit">Sign in</button>
            </form>
        </div>
    </div>
@endsection
