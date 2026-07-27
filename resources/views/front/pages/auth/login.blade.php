@extends('front.layout.layout')

@section('title', 'Sign In | Ledrix')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0 auth-shell">
                <div class="col-lg-5 auth-aside">
                    <div class="auth-aside-inner">
                        <a href="{{ route('index.get') }}" class="d-inline-block mb-4">
                            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM logo" style="max-width:140px;height:auto;">
                        </a>
                        <span class="auth-brand-badge"><i class="bi bi-shield-check"></i> Secure workspace access</span>
                        <h1>Sign in to Ledrix</h1>
                        <p class="auth-aside-lead">Manage your subscription, billing, and CRM from one tenant dashboard.</p>
                        <ul class="auth-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Trial & plan status at a glance</li>
                            <li><i class="bi bi-check-circle-fill"></i> One-click CRM admin access</li>
                            <li><i class="bi bi-check-circle-fill"></i> Tenant-isolated data</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7 auth-main">
                    <div class="auth-card">
                        <div class="auth-card-header mb-4">
                            <h2>Welcome back</h2>
                            <p>Use your work email and password to continue.</p>
                        </div>
                        <form method="POST" action="{{ route('tenant.login.post') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="auth-label" for="email">Work email</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-envelope auth-input-icon"></i>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="you@company.com" required autofocus>
                                </div>
                                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="auth-label" for="password">Password</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-lock auth-input-icon"></i>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="••••••••" required>
                                    <button type="button" class="auth-input-toggle" data-toggle-password="#password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary auth-btn-primary w-100">Sign in</button>
                        </form>
                        <p class="auth-footer-link">
                            New to Ledrix? <a href="{{ route('pricing.get') }}">Start free trial</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/auth.js') }}" defer></script>
@endpush
