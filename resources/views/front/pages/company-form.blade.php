@extends('front.layout.layout')

@section('title', 'Company Details | Ledrix')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    @php
        $package = $pkg ?? null;
    @endphp

    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0 auth-shell">
                <div class="col-lg-4 auth-aside">
                    <div class="auth-aside-inner">
                        <a href="{{ route('index.get') }}" class="d-inline-block mb-4">
                            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM logo" style="max-width:140px;height:auto;">
                        </a>
                        @if ($package)
                            <span class="auth-brand-badge"><i class="bi bi-stars"></i> {{ $package->trial_days }}-day trial</span>
                        @endif
                        <h1>Complete your company profile</h1>
                        <p class="auth-aside-lead">
                            Add billing details so we can activate your workspace after email verification.
                        </p>
                        <ul class="auth-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Secure billing information</li>
                            <li><i class="bi bi-check-circle-fill"></i> No charge during trial</li>
                            <li><i class="bi bi-check-circle-fill"></i> Edit anytime from dashboard</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-8 auth-main">
                    <div class="auth-register-layout">
                        @if ($package)
                            <div class="auth-plan-sticky d-none d-lg-block">
                                <div class="auth-plan-card">
                                    <div class="plan-name">{{ $package->name }}</div>
                                    <div class="auth-plan-price mt-2">
                                        ${{ number_format($package->monthly_price, 0) }}
                                        <small>/mo after trial</small>
                                    </div>
                                    @if ($package->description)
                                        <p class="small text-muted mt-2 mb-0">{{ $package->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="auth-card auth-card-wide">
                            <div class="auth-card-header mb-4">
                                <h2>Company details</h2>
                                <p>
                                    @if ($package)
                                        Selected plan: <strong>{{ $package->name }}</strong>
                                    @else
                                        Select a plan on the <a href="{{ route('pricing.get') }}">pricing page</a> first.
                                    @endif
                                </p>
                            </div>

                            @unless ($package)
                                <a href="{{ route('pricing.get') }}" class="btn btn-primary auth-btn-primary w-100">Choose a plan</a>
                            @else
                                <form method="POST" action="{{ route('tenant.register.store') }}">
                                    @csrf
                                    <input type="hidden" name="pkg_slug" value="{{ $package->slug }}">

                                    <p class="auth-section-title">Account</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="auth-label" for="company-name">Company name</label>
                                            <div class="auth-input-group">
                                                <i class="bi bi-building auth-input-icon"></i>
                                                <input type="text" id="company-name" name="name" value="{{ old('name') }}"
                                                    class="form-control @error('name') is-invalid @enderror" required>
                                            </div>
                                            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="work-email">Work email</label>
                                            <div class="auth-input-group">
                                                <i class="bi bi-envelope auth-input-icon"></i>
                                                <input type="email" id="work-email" name="email" value="{{ old('email') }}"
                                                    class="form-control @error('email') is-invalid @enderror" required>
                                            </div>
                                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="password">Password</label>
                                            <div class="auth-input-group">
                                                <i class="bi bi-lock auth-input-icon"></i>
                                                <input type="password" id="password" name="password"
                                                    class="form-control @error('password') is-invalid @enderror" required>
                                                <button type="button" class="auth-input-toggle" data-toggle-password="#password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="password_confirmation">Confirm password</label>
                                            <input type="password" id="password_confirmation" name="password_confirmation"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="phone">Phone</label>
                                            <div class="auth-phone-field">
                                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                                    class="form-control @error('phone') is-invalid @enderror"
                                                    data-phone-input data-phone-required="1" data-phone-sync-country="1"
                                                    data-initial-country="{{ strtolower(old('country', 'PK')) }}"
                                                    placeholder="Phone number" required autocomplete="tel">
                                            </div>
                                            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="country">Country</label>
                                            <select id="country" name="country" class="form-select @error('country') is-invalid @enderror" required>
                                                <option value="">Select country</option>
                                                @foreach (['PK' => 'Pakistan', 'US' => 'United States', 'GB' => 'United Kingdom', 'IN' => 'India', 'AE' => 'UAE', 'CA' => 'Canada', 'SA' => 'Saudi Arabia'] as $code => $label)
                                                    <option value="{{ $code }}" @selected(old('country') === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('country')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="auth-label" for="website">Website</label>
                                            <input type="url" id="website" name="website" value="{{ old('website') }}" class="form-control" placeholder="https://">
                                        </div>
                                    </div>

                                    <div class="auth-divider"></div>
                                    <p class="auth-section-title">Billing</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="auth-label" for="billing-name">Billing contact</label>
                                            <input type="text" id="billing-name" name="billing_name" value="{{ old('billing_name') }}"
                                                class="form-control @error('billing_name') is-invalid @enderror" required>
                                            @error('billing_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="billing-email">Billing email</label>
                                            <input type="email" id="billing-email" name="billing_email" value="{{ old('billing_email') }}"
                                                class="form-control @error('billing_email') is-invalid @enderror" required>
                                            @error('billing_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label">Billing cycle</label>
                                            <div class="auth-cycle-toggle">
                                                <input type="radio" name="billing_cycle" id="cycle-monthly" value="monthly" @checked(old('billing_cycle', 'monthly') === 'monthly')>
                                                <label for="cycle-monthly">Monthly</label>
                                                <input type="radio" name="billing_cycle" id="cycle-yearly" value="yearly" @checked(old('billing_cycle') === 'yearly')>
                                                <label for="cycle-yearly">Yearly</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="auth-label" for="billing-phone">Billing phone</label>
                                            <div class="auth-phone-field">
                                                <input type="tel" id="billing-phone" name="billing_phone" value="{{ old('billing_phone') }}"
                                                    class="form-control @error('billing_phone') is-invalid @enderror"
                                                    data-phone-input
                                                    data-initial-country="{{ strtolower(old('country', 'PK')) }}"
                                                    placeholder="Billing phone" autocomplete="tel">
                                            </div>
                                            @error('billing_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="auth-label" for="billing-address">Billing address</label>
                                            <textarea id="billing-address" name="billing_address" rows="2"
                                                class="form-control @error('billing_address') is-invalid @enderror" required>{{ old('billing_address') }}</textarea>
                                            @error('billing_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="auth-label" for="address">Company address</label>
                                            <input type="text" id="address" name="address" value="{{ old('address') }}" class="form-control">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary auth-btn-primary w-100 mt-4">
                                        Start {{ $package->trial_days }}-day free trial
                                    </button>
                                </form>
                            @endunless

                            <p class="auth-footer-link mb-0">
                                <a href="{{ route('pricing.get') }}">Change plan</a>
                                ·
                                <a href="{{ route('tenant.login') }}">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('head')
    @include('front.includes.phone-input-styles')
@endpush

@push('scripts')
    @include('front.includes.phone-input-assets')
    <script src="{{ asset('front-assets/js/auth.js') }}" defer></script>
@endpush
