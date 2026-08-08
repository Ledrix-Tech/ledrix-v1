@extends('front.layout.lp')

@section('title', 'Start free trial')

@section('seo_title', 'Ledrix CRM Free Trial — Agency Sales CRM for Leads & Closers')
@section('meta_description', 'Start a free Ledrix CRM trial. Capture leads, assign sellers, collect payments, and run your agency pipeline — no credit card required.')
@section('robots', 'noindex, follow')

@section('main-content')
    <div class="mkt-page lp-page">
        <section class="mkt-hero text-center">
            <div class="container mkt-hero-inner px-3 px-sm-4">
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> {{ $trialDays }}-day free trial</span>
                <h1>Get more clients with Ledrix CRM</h1>
                <p class="mkt-hero-lead">
                    Capture leads, assign sellers, send payment links, and run your agency pipeline in one tenant-isolated workspace — no credit card required.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ $registerUrl }}" class="btn btn-lg mkt-btn-primary" data-lp-cta="trial-hero">
                        Start free trial
                        @if ($package)
                            <span class="opacity-75">· {{ $package->name }}</span>
                        @endif
                    </a>
                    <a href="{{ route('lp.demo') }}" class="btn btn-lg mkt-btn-ghost">Book a demo</a>
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                    <span><i class="bi bi-shield-lock"></i> Tenant-isolated CRM</span>
                    <span><i class="bi bi-box-arrow-in-right"></i> Live in minutes</span>
                </div>
            </div>
        </section>

        <section class="mkt-section mkt-section-alt">
            <div class="container text-center px-3 px-sm-4">
                <h2 class="mkt-section-title">Built for teams who live on leads</h2>
                <p class="mkt-section-lead">
                    Turn inbound demand into booked calls and paid clients — without spreadsheet chaos.
                </p>
                <div class="mkt-grid-3 text-start">
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-inbox"></i></div>
                        <h5>Lead capture that sticks</h5>
                        <p>Ingest from web forms, scripts, and API — then route to the right seller fast.</p>
                    </div>
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-people"></i></div>
                        <h5>Seller + admin workspaces</h5>
                        <p>Admins see the full pipeline; sellers get assigned leads, orders, and follow-ups.</p>
                    </div>
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-cash-coin"></i></div>
                        <h5>Get paid inside the CRM</h5>
                        <p>Stripe and PayPal payment links, client portal, and order tracking in one place.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-section">
            <div class="container px-3 px-sm-4" style="max-width: 720px;">
                <h2 class="mkt-section-title text-center">Common questions</h2>
                <div class="accordion mkt-faq mt-4" id="lpTrialFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Do I need a credit card to start?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                No. Start the {{ $trialDays }}-day trial free. You only pay if you continue after the trial.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Who is Ledrix for?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                Agencies, closers, and sales teams that need multi-tenant CRM: leads, sellers, orders, and client payments.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How fast can I go live?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                Create your workspace, verify email, and open Admin CRM — usually in a few minutes.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-cta-band text-center">
            <div class="container px-3 px-sm-4">
                <h2>Ready to grow your pipeline?</h2>
                <p>Start your free Ledrix trial and onboard your first leads today.</p>
                <a href="{{ $registerUrl }}" class="btn btn-lg mkt-btn-primary" data-lp-cta="trial-footer">Start free trial</a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-lp-cta]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (typeof fbq === 'function') {
                fbq('trackCustom', 'LpCtaClick', { cta: el.getAttribute('data-lp-cta') });
            }
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ event: 'lp_cta_click', cta: el.getAttribute('data-lp-cta') });
        });
    });
</script>
@endpush
