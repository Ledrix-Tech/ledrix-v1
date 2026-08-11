@extends('front.layout.layout')

@section('title', 'Pricing')

@section('seo_title', 'Ledrix CRM Pricing — Plans & Free Trial')
@section('meta_description', 'Compare Ledrix CRM pricing plans for agencies and sales teams. Multi-tenant workspaces, seller limits, payment modules, and a 14-day free trial — no credit card required.')
@section('meta_keywords', 'Ledrix pricing, CRM pricing, sales CRM cost, agency CRM plans, SaaS CRM subscription, free trial CRM, Ledrix CRM plans')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Pricing', 'url' => route('pricing.get')],
    ]])
    @if ($packages->isNotEmpty())
        <script type="application/ld+json">
        {!! json_encode([
            '@'.'context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Ledrix CRM Subscription',
            'description' => 'Multi-tenant sales CRM software with plan-based modules and free trial.',
            'brand' => ['@type' => 'Brand', 'name' => 'Ledrix'],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'USD',
                'lowPrice' => (string) ($packages->min('monthly_price') ?: 0),
                'highPrice' => (string) ($packages->max('monthly_price') ?: 0),
                'offerCount' => $packages->count(),
                'offers' => $packages->map(fn ($p) => [
                    '@type' => 'Offer',
                    'name' => $p->name ?? 'Plan',
                    'price' => (string) ($p->monthly_price ?? 0),
                    'priceCurrency' => 'USD',
                    'url' => route('tenant.register.form', $p->slug),
                    'availability' => 'https://schema.org/InStock',
                ])->values()->all(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
    @include('front.includes.schema-faq', ['faqs' => config('seo.pricing_faq', [])])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/pricing.css') }}">
@endpush

@section('main-content')
    @php
        $formatLimit = fn ($value) => (int) $value === -1 ? 'Unlimited' : number_format((int) $value);
        $maxTrialDays = $packages->max('trial_days') ?: 14;
    @endphp

    <div class="pricing-page">
        {{-- Hero --}}
        <section class="pricing-hero text-center">
            <div class="container pricing-hero-inner">
                <span class="pricing-hero-badge"><i class="bi bi-stars"></i> Simple, transparent pricing</span>
                <h1>Ledrix CRM pricing — plans &amp; free trial</h1>
                <p class="pricing-hero-lead">
                    Compare Ledrix CRM plans for agencies and sales teams. Start with a free trial on any package — full CRM access from day one, no credit card required.
                    Need help choosing? See our <a href="{{ route('faq.get') }}">CRM FAQ</a> or <a href="{{ route('contact-us.get') }}">contact sales</a>.
                </p>

                <div class="pricing-trust-row mb-4">
                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                    <span><i class="bi bi-shield-check"></i> Tenant-isolated data</span>
                    <span><i class="bi bi-arrow-repeat"></i> Cancel anytime</span>
                </div>

                @if ($packages->isNotEmpty())
                    <div class="pricing-billing-toggle">
                        <label data-billing-label="monthly" class="active">Monthly</label>
                        <label class="pricing-switch">
                            <input type="checkbox" id="pricing-billing-toggle" aria-label="Toggle yearly billing">
                            <span class="pricing-switch-slider"></span>
                        </label>
                        <label data-billing-label="yearly">Yearly</label>
                        <span class="pricing-save-badge">Save with annual</span>
                    </div>
                @endif
            </div>
        </section>

        {{-- Plan cards --}}
        <section class="pricing-cards-section">
            <div class="container">
                @if ($packages->isEmpty())
                    <div class="pricing-empty">
                        <i class="bi bi-inbox display-6 d-block mb-3 text-secondary"></i>
                        <h4 class="fw-bold text-dark">No plans published yet</h4>
                        <p class="mb-0">Check back soon or <a href="{{ route('contact-us.get') }}">contact us</a> for enterprise pricing.</p>
                    </div>
                @else
                    <div class="row g-4 justify-content-center">
                        @foreach ($packages as $package)
                            <div class="col-md-6 col-lg-4">
                                <div class="pricing-card {{ $package->is_popular ? 'is-popular' : '' }}">
                                    @if ($package->badge_text || $package->is_popular)
                                        <div class="pricing-card-badge">
                                            {{ $package->badge_text ?: 'Most popular' }}
                                        </div>
                                    @endif

                                    <div class="pricing-card-body">
                                        <div class="pricing-card-name">{{ $package->name }}</div>
                                        <p class="pricing-card-desc mb-0">{{ $package->description ?: 'Everything you need to run your CRM workspace.' }}</p>

                                        <div class="pricing-card-price"
                                            data-price-monthly="{{ $package->monthly_price }}"
                                            data-price-yearly="{{ $package->yearly_price ?: $package->monthly_price * 12 }}">
                                            <span class="amount">${{ number_format($package->monthly_price, $package->monthly_price == floor($package->monthly_price) ? 0 : 2) }}</span>
                                            <span class="period">/month</span>
                                        </div>

                                        @if ($package->trial_days > 0)
                                            <div class="pricing-trial-pill">
                                                <i class="bi bi-gift"></i>
                                                {{ $package->trial_days }}-day free trial
                                            </div>
                                        @endif

                                        <div class="pricing-limits-row">
                                            <span class="pricing-limit-chip">{{ $formatLimit($package->max_sellers) }} sellers</span>
                                            <span class="pricing-limit-chip">{{ $formatLimit($package->max_leads_per_month) }} leads/mo</span>
                                            <span class="pricing-limit-chip">{{ $formatLimit($package->max_brands) }} brands</span>
                                        </div>

                                        @if ($package->features_html)
                                            <div class="pricing-features">{!! $package->features_html !!}</div>
                                        @endif

                                        <div class="pricing-card-cta">
                                            <a href="{{ route('tenant.register.form', array_filter(['slug' => $package->slug, 'ref' => request('ref')])) }}"
                                                class="btn w-100 {{ $package->is_popular ? 'btn-primary pricing-btn' : 'pricing-btn pricing-btn-outline' }}">
                                                Start {{ $package->trial_days ?: $maxTrialDays }}-day free trial
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-center text-muted small mt-4 mb-0">
                        All plans include email verification, tenant dashboard, and one-click CRM access.
                        <a href="{{ route('tenant.login') }}">Already have an account?</a>
                    </p>
                @endif
            </div>
        </section>

        {{-- Dynamic comparison table --}}
        @if ($packages->count() >= 2)
            <section class="pricing-compare-section">
                <div class="container">
                    <h2 class="text-center mb-2">Compare plans</h2>
                    <p class="text-center text-muted mb-4">Limits and modules pulled live from your super-admin package settings.</p>

                    <div class="pricing-compare-wrap">
                        <table class="table pricing-compare-table mb-0">
                            <thead>
                                <tr>
                                    <th>What's included</th>
                                    @foreach ($packages as $package)
                                        <th>
                                            <div class="fw-bold">{{ $package->name }}</div>
                                            <small class="text-muted">${{ number_format($package->monthly_price, 0) }}/mo</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="pricing-compare-group">
                                    <td colspan="{{ $packages->count() + 1 }}">Usage limits</td>
                                </tr>
                                @foreach ($limitRows as $key => $label)
                                    <tr>
                                        <td class="feature-label">{{ $label }}</td>
                                        @foreach ($packages as $package)
                                            <td><span class="limit-val">{{ $formatLimit($package->$key) }}</span></td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <tr class="pricing-compare-group">
                                    <td colspan="{{ $packages->count() + 1 }}">Modules & features</td>
                                </tr>
                                @foreach ($featureRows as $key => $label)
                                    <tr>
                                        <td class="feature-label">{{ $label }}</td>
                                        @foreach ($packages as $package)
                                            <td>
                                                @if ($package->$key)
                                                    <span class="check-yes"><i class="bi bi-check-circle-fill"></i></span>
                                                @else
                                                    <span class="check-no"><i class="bi bi-dash-circle"></i></span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @include('front.includes.pricing-faq-section')

        {{-- Bottom CTA --}}
        @if ($packages->isNotEmpty())
            <section class="pricing-cta-band">
                <div class="container">
                    <h2>Ready to launch your CRM workspace?</h2>
                    <p>Start your free trial in minutes — setup, verify email, and go live.</p>
                    @php $featured = $packages->firstWhere('is_popular', true) ?? $packages->first(); @endphp
                    <a href="{{ route('tenant.register.form', array_filter(['slug' => $featured->slug, 'ref' => request('ref')])) }}" class="btn btn-light btn-lg fw-bold px-4">
                        Start {{ $featured->trial_days ?: $maxTrialDays }}-day free trial
                    </a>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/pricing.js') }}" defer></script>
@endpush
