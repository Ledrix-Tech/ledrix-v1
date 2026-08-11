@extends('front.layout.layout')

@section('title', 'Start Free Trial')

@section('seo_title', 'Start Ledrix CRM Free Trial — Register Your Workspace')
@section('meta_description', 'Register for Ledrix CRM and start your free trial. Multi-tenant sales CRM with lead management, seller panels, and payment links — no credit card required.')
@section('meta_keywords', 'Ledrix free trial, CRM registration, start CRM trial, sales CRM signup, agency CRM trial')
@section('robots', 'noindex, follow')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0 auth-shell">
                {{-- Brand panel --}}
                <div class="col-lg-4 auth-aside">
                    <div class="auth-aside-inner">
                        <a href="{{ route('index.get') }}" class="d-inline-block mb-4">
                            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM logo" style="max-width:140px;height:auto;">
                        </a>
                        <span class="auth-brand-badge"><i class="bi bi-stars"></i> {{ $package->trial_days }}-day free trial</span>
                        <h1>Start selling smarter today</h1>
                        <p class="auth-aside-lead">
                            Join teams using Ledrix to capture leads, close deals, and manage clients — without the complexity.
                        </p>
                        <ul class="auth-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Full CRM access during trial</li>
                            <li><i class="bi bi-check-circle-fill"></i> No credit card required</li>
                            <li><i class="bi bi-check-circle-fill"></i> Cancel anytime before trial ends</li>
                            <li><i class="bi bi-check-circle-fill"></i> Setup in under 5 minutes</li>
                        </ul>
                    </div>
                    <div class="auth-trust-row auth-aside-inner">
                        <span><i class="bi bi-people-fill"></i> Multi-tenant SaaS</span>
                        <span><i class="bi bi-graph-up-arrow"></i> Built for agencies</span>
                    </div>
                </div>

                {{-- Form panel --}}
                <div class="col-lg-8 auth-main">
                    <div class="auth-register-layout">
                        {{-- Plan summary --}}
                        <div class="auth-plan-sticky d-none d-lg-block">
                            <div class="auth-plan-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="plan-name">{{ $package->name }}</div>
                                        @if ($package->badge_text)
                                            <span class="badge bg-primary mt-1">{{ $package->badge_text }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('pricing.get') }}" class="btn btn-sm btn-link text-decoration-none p-0">Change</a>
                                </div>
                                <div class="auth-plan-price mt-2">
                                    ${{ number_format($package->monthly_price, 0) }}
                                    <small>/mo after trial</small>
                                </div>
                                @if ($package->description)
                                    <p class="small text-muted mt-2 mb-3">{{ $package->description }}</p>
                                @endif
                                <ul class="list-unstyled small text-secondary mb-0">
                                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> {{ $package->max_sellers == -1 ? 'Unlimited' : $package->max_sellers }} sellers</li>
                                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> {{ $package->max_leads_per_month == -1 ? 'Unlimited' : $package->max_leads_per_month }} leads/mo</li>
                                    <li><i class="bi bi-check2 text-success me-1"></i> {{ $package->max_brands == -1 ? 'Unlimited' : $package->max_brands }} brands</li>
                                </ul>
                            </div>
                            <div class="auth-trial-banner">
                                <i class="bi bi-gift-fill"></i>
                                <div>
                                    <strong>Free for {{ $package->trial_days }} days</strong>
                                    <p>You won't be charged until your trial ends. Verify your email to activate.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Registration form --}}
                        <div class="auth-card auth-card-wide">
                            <div class="auth-steps d-none d-md-flex">
                                <div class="auth-step done"><span><i class="bi bi-check"></i></span> Plan</div>
                                <div class="auth-step active"><span>2</span> Account</div>
                                <div class="auth-step"><span>3</span> Billing</div>
                            </div>

                            {{-- Mobile plan summary --}}
                            <div class="auth-plan-card d-lg-none mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="plan-name">{{ $package->name }}</div>
                                        <div class="auth-plan-price mt-1">${{ number_format($package->monthly_price, 0) }}<small>/mo</small></div>
                                    </div>
                                    <a href="{{ route('pricing.get') }}" class="btn btn-sm btn-outline-secondary">Change plan</a>
                                </div>
                            </div>

                            <div class="auth-card-header">
                                <h2>Create your workspace</h2>
                                <p>Tell us about your company to start your free trial.</p>
                            </div>

                            <form method="POST" action="{{ route('tenant.register.store') }}">
                                @csrf
                                <input type="hidden" name="pkg_slug" value="{{ $package->slug }}">

                                <p class="auth-section-title">Company & account</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="auth-label" for="company-name">Company name</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-building auth-input-icon"></i>
                                            <input type="text" id="company-name" name="name" value="{{ old('name') }}"
                                                class="form-control @error('name') is-invalid @enderror"
                                                placeholder="Acme Agency" required>
                                        </div>
                                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="work-email">Work email</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-envelope auth-input-icon"></i>
                                            <input type="email" id="work-email" name="email" value="{{ old('email') }}"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="you@company.com" required>
                                        </div>
                                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="password">Password</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-lock auth-input-icon"></i>
                                            <input type="password" id="password" name="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="Min. 8 characters" required>
                                            <button type="button" class="auth-input-toggle" data-toggle-password="#password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="password_confirmation">Confirm password</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-shield-lock auth-input-icon"></i>
                                            <input type="password" id="password_confirmation" name="password_confirmation"
                                                class="form-control" placeholder="Repeat password" required>
                                        </div>
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
                                        <div class="form-text">Select country code, then enter a real mobile number.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="country">Country</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-globe2 auth-input-icon"></i>
                                            <select id="country" name="country" class="form-select @error('country') is-invalid @enderror" required>
                                                <option value="">Select country</option>
                                                @foreach (['PK' => 'Pakistan', 'US' => 'United States', 'GB' => 'United Kingdom', 'IN' => 'India', 'AE' => 'United Arab Emirates', 'CA' => 'Canada', 'SA' => 'Saudi Arabia'] as $code => $label)
                                                    <option value="{{ $code }}" @selected(old('country') === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('country')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-label" for="website">Website <span class="text-muted fw-normal">(optional)</span></label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-link-45deg auth-input-icon"></i>
                                            <input type="url" id="website" name="website" value="{{ old('website') }}"
                                                class="form-control @error('website') is-invalid @enderror"
                                                placeholder="https://yourcompany.com">
                                        </div>
                                        @error('website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="auth-divider"></div>
                                <p class="auth-section-title">Billing details</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="auth-label" for="billing-name">Billing contact</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-person auth-input-icon"></i>
                                            <input type="text" id="billing-name" name="billing_name" value="{{ old('billing_name') }}"
                                                class="form-control @error('billing_name') is-invalid @enderror"
                                                placeholder="Full name" required>
                                        </div>
                                        @error('billing_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="billing-email">Billing email</label>
                                        <div class="auth-input-group">
                                            <i class="bi bi-envelope-at auth-input-icon"></i>
                                            <input type="email" id="billing-email" name="billing_email" value="{{ old('billing_email') }}"
                                                class="form-control @error('billing_email') is-invalid @enderror"
                                                placeholder="billing@company.com" required>
                                        </div>
                                        @error('billing_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label">Billing cycle</label>
                                        <div class="auth-cycle-toggle">
                                            <input type="radio" name="billing_cycle" id="cycle-monthly" value="monthly"
                                                @checked(old('billing_cycle', 'monthly') === 'monthly')>
                                            <label for="cycle-monthly">Monthly</label>
                                            <input type="radio" name="billing_cycle" id="cycle-yearly" value="yearly"
                                                @checked(old('billing_cycle') === 'yearly')>
                                            <label for="cycle-yearly">Yearly</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="billing-phone">Billing phone <span class="text-muted fw-normal">(optional)</span></label>
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
                                            class="form-control @error('billing_address') is-invalid @enderror"
                                            placeholder="Street, city, postal code" required>{{ old('billing_address') }}</textarea>
                                        @error('billing_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-label" for="address">Company address <span class="text-muted fw-normal">(optional)</span></label>
                                        <input type="text" id="address" name="address" value="{{ old('address') }}"
                                            class="form-control" placeholder="If different from billing">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-label" for="referral-code">Referral code <span class="text-muted fw-normal">(optional)</span></label>
                                        <input type="text" id="referral-code" name="referral_code"
                                            value="{{ old('referral_code', request('ref')) }}"
                                            class="form-control @error('referral_code') is-invalid @enderror"
                                            placeholder="e.g. NOVA8X2K" maxlength="20">
                                        @error('referral_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="auth-trial-banner d-lg-none">
                                    <i class="bi bi-gift-fill"></i>
                                    <div>
                                        <strong>{{ $package->trial_days }}-day free trial</strong>
                                        <p>No payment now. Trial starts after email verification.</p>
                                    </div>
                                </div>

                                <p class="small text-muted mt-3 mb-0">
                                    By creating an account, you agree to Ledrix's
                                    <a href="{{ route('terms.get') }}" target="_blank" rel="noopener">Terms of Service</a>
                                    and
                                    <a href="{{ route('privacy.get') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                                </p>

                                <button type="submit" class="btn btn-primary auth-btn-primary w-100 mt-3">
                                    <i class="bi bi-rocket-takeoff me-1"></i> Start {{ $package->trial_days }}-day free trial
                                </button>

                                <p class="auth-footer-link">
                                    Already have an account?
                                    <a href="{{ route('tenant.login') }}">Sign in</a>
                                </p>
                            </form>
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
