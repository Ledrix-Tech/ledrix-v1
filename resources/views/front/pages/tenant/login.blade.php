@extends('front.layout.layout')

@section('title', 'Sign In')

@section('robots', 'noindex, nofollow')
@section('meta_description', 'Sign in to your Ledrix CRM tenant workspace to manage billing and launch your sales CRM.')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0 auth-shell">
                {{-- Brand panel --}}
                <div class="col-lg-5 auth-aside">
                    <div class="auth-aside-inner">
                        <a href="{{ route('index.get') }}" class="d-inline-block mb-4">
                            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM logo" style="max-width:140px;height:auto;">
                        </a>
                        <span class="auth-brand-badge"><i class="bi bi-shield-check"></i> Trusted by sales teams</span>
                        <h1>Welcome back to your workspace</h1>
                        <p class="auth-aside-lead">
                            Manage your subscription, launch your CRM, and keep your pipeline moving — all from one place.
                        </p>
                        <ul class="auth-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Access billing & trial status instantly</li>
                            <li><i class="bi bi-check-circle-fill"></i> One-click entry to your CRM admin panel</li>
                            <li><i class="bi bi-check-circle-fill"></i> Secure, tenant-isolated workspace</li>
                        </ul>
                    </div>
                    <div class="auth-trust-row auth-aside-inner">
                        <span><i class="bi bi-lock-fill"></i> SSL encrypted</span>
                        <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                        <span><i class="bi bi-headset"></i> 24/7 support</span>
                    </div>
                </div>

                {{-- Form panel --}}
                <div class="col-lg-7 auth-main">
                    <div class="auth-card">
                        <div class="auth-card-header mb-4">
                            <h2>Sign in</h2>
                            <p>Enter your credentials to access your Ledrix account.</p>
                        </div>

                        <form method="POST" action="{{ route('tenant.login.post') }}" novalidate>
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

                            <div class="mb-3">
                                <label class="auth-label" for="password">Password</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-lock auth-input-icon"></i>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="••••••••" required>
                                    <button type="button" class="auth-input-toggle" data-toggle-password="#password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary auth-btn-primary w-100">
                                Sign in to Ledrix
                            </button>
                        </form>

                        <div class="auth-resend-box">
                            <p class="small fw-semibold mb-2 mb-md-1 text-secondary">Need to verify your email?</p>
                            <form method="POST" action="{{ route('tenant.verify-email.resend') }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-sm-8">
                                    <input type="email" name="email" value="{{ old('email') }}"
                                        class="form-control form-control-sm" placeholder="Account email" required>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Resend link</button>
                                </div>
                            </form>
                        </div>

                        <p class="auth-footer-link">
                            Don't have an account?
                            <a href="{{ route('pricing.get') }}">Start free trial</a>
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
