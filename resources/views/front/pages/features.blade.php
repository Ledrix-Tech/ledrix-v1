@extends('front.layout.layout')

@section('title', 'Features')

@section('seo_title', 'Ledrix CRM Features — Lead Management, Payments & Client Portal')
@section('meta_description', 'Explore Ledrix CRM features: multi-brand lead routing, seller and admin panels, Stripe and PayPal payment links, client portal, Upwork module, tenant isolation, and performance tracking.')
@section('meta_keywords', 'CRM features, lead management CRM, sales pipeline software, payment links CRM, client portal CRM, multi-tenant CRM features, Ledrix features, agency sales tools')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Features', 'url' => route('features.get')],
    ]])
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Ledrix CRM Features',
        'description' => 'Core sales CRM capabilities included in Ledrix.',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Lead management'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Seller & admin panels'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Orders & payments'],
            ['@type' => 'ListItem', 'position' => 4, 'name' => 'Client portal'],
            ['@type' => 'ListItem', 'position' => 5, 'name' => 'Upwork module'],
            ['@type' => 'ListItem', 'position' => 6, 'name' => 'Tenant isolation'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page">
        {{-- Hero --}}
        <section class="mkt-hero text-center">
            <div class="container mkt-hero-inner">
                <span class="mkt-hero-badge"><i class="bi bi-grid-1x2-fill"></i> Full-stack sales CRM</span>
                <h1>Ledrix CRM features for modern sales teams</h1>
                <p class="mkt-hero-lead">
                    Ledrix covers the full revenue cycle — lead capture, seller assignment, orders, payments, and client access — built for multi-tenant SaaS from the ground up.
                    Compare CRM modules, payment integrations, and plan limits on our <a href="{{ route('pricing.get') }}">pricing page</a>.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">Start free trial</a>
                    <a href="{{ route('features.get') }}#capabilities" class="btn btn-lg mkt-btn-ghost">Explore features</a>
                </div>
            </div>
        </section>

        {{-- Overview grid --}}
        <section class="mkt-section mkt-section-alt">
            <div class="container text-center">
                <h2 class="mkt-section-title">Core capabilities</h2>
                <p class="mkt-section-lead">Modular features you can enable per plan — from lean starter teams to full agency operations.</p>
                <div class="mkt-grid-3">
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-funnel"></i></div>
                        <h5>Lead management</h5>
                        <p>Capture, assign, and track leads across brands and sellers with full activity history.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-people"></i></div>
                        <h5>Seller & admin panels</h5>
                        <p>Role-based dashboards for closers, team leads, and admins — each sees what they need.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-credit-card"></i></div>
                        <h5>Orders & payments</h5>
                        <p>Payment links, Stripe/PayPal support, milestone billing, and order tracking built in.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-badge"></i></div>
                        <h5>Client portal</h5>
                        <p>Give clients a secure login to view orders, tickets, and project progress.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-briefcase"></i></div>
                        <h5>Upwork module</h5>
                        <p>Manage Upwork clients, orders, and payment links alongside your main CRM pipeline.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-shield-check"></i></div>
                        <h5>Tenant isolation</h5>
                        <p>Every workspace is scoped by tenant ID — your data stays separate and secure.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Deep dive --}}
        <section class="mkt-section mkt-section-muted" id="capabilities">
            <div class="container">
                <h2 class="mkt-section-title text-center mb-5">Deep dive</h2>

                <div class="row mkt-feature-row g-4">
                    <div class="col-lg-6">
                        <span class="mkt-feature-tag">Pipeline</span>
                        <h3>Lead & contact management</h3>
                        <p>Centralize every inquiry in one hub. Tag, filter, and assign leads to the right seller or brand without losing context.</p>
                        <ul class="mkt-check-list">
                            <li><i class="bi bi-check-circle-fill"></i> Multi-brand lead routing</li>
                            <li><i class="bi bi-check-circle-fill"></i> Assignment history & notes</li>
                            <li><i class="bi bi-check-circle-fill"></i> API & webhook ingestion</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="{{ asset('front-assets/imgs/lead-m.jpg') }}" alt="Ledrix CRM lead management dashboard for sales teams" class="mkt-feature-img" loading="lazy">
                    </div>
                </div>

                <div class="row mkt-feature-row g-4 flex-lg-row-reverse">
                    <div class="col-lg-6">
                        <span class="mkt-feature-tag">Automation</span>
                        <h3>Seller workflows</h3>
                        <p>Reduce manual handoffs with structured assignment flows, performance bonuses, and leaderboards that keep teams accountable.</p>
                        <ul class="mkt-check-list">
                            <li><i class="bi bi-check-circle-fill"></i> Seller leaderboard</li>
                            <li><i class="bi bi-check-circle-fill"></i> Performance bonus tracking</li>
                            <li><i class="bi bi-check-circle-fill"></i> Lead prediction (on eligible plans)</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="{{ asset('front-assets/imgs/automation.jpg') }}" alt="Seller workflow automation in Ledrix sales CRM" class="mkt-feature-img" loading="lazy">
                    </div>
                </div>

                <div class="row mkt-feature-row g-4">
                    <div class="col-lg-6">
                        <span class="mkt-feature-tag">Revenue</span>
                        <h3>Orders, payments & invoicing</h3>
                        <p>Generate payment links, track orders through completion, and support dual invoicing and chargeback tracking on higher tiers.</p>
                        <ul class="mkt-check-list">
                            <li><i class="bi bi-check-circle-fill"></i> Stripe & PayPal</li>
                            <li><i class="bi bi-check-circle-fill"></i> Milestone payments</li>
                            <li><i class="bi bi-check-circle-fill"></i> Payment link management</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="{{ asset('front-assets/imgs/chatt.jpg') }}" alt="Stripe and PayPal payment links inside Ledrix CRM" class="mkt-feature-img" loading="lazy">
                    </div>
                </div>

                <div class="row mkt-feature-row g-4 flex-lg-row-reverse">
                    <div class="col-lg-6">
                        <span class="mkt-feature-tag">Integrations</span>
                        <h3>API & webhooks</h3>
                        <p>Connect your stack with REST API access and webhooks on eligible plans. Ingest leads, sync status, and build custom workflows.</p>
                        <ul class="mkt-check-list">
                            <li><i class="bi bi-check-circle-fill"></i> Tenant-scoped API keys</li>
                            <li><i class="bi bi-check-circle-fill"></i> Webhook events</li>
                            <li><i class="bi bi-check-circle-fill"></i> Custom domain & white-label options</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="{{ asset('front-assets/imgs/integerate.jpg') }}" alt="Ledrix CRM API and webhook integrations for lead intake" class="mkt-feature-img" loading="lazy">
                    </div>
                </div>

                <div class="row mkt-feature-row g-4">
                    <div class="col-lg-6">
                        <span class="mkt-feature-tag">Insights</span>
                        <h3>Reporting & dashboards</h3>
                        <p>Admin dashboards show leads, orders, seller performance, and usage against your plan limits — always up to date.</p>
                        <ul class="mkt-check-list">
                            <li><i class="bi bi-check-circle-fill"></i> Real-time usage metrics</li>
                            <li><i class="bi bi-check-circle-fill"></i> Plan limit visibility</li>
                            <li><i class="bi bi-check-circle-fill"></i> Project & task tracking</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="{{ asset('front-assets/imgs/report.jpg') }}" alt="Ledrix CRM admin reporting and seller performance dashboards" class="mkt-feature-img" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        {{-- Teams --}}
        <section class="mkt-section mkt-section-alt">
            <div class="container text-center">
                <h2 class="mkt-section-title">Built for every role</h2>
                <div class="mkt-grid-3 mt-4">
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-workspace"></i></div>
                        <h5>Sellers</h5>
                        <p>Focused panel for assigned leads, follow-ups, and closing — without admin clutter.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-shield-lock"></i></div>
                        <h5>Admins</h5>
                        <p>Full CRM control: brands, users, orders, payments, and tenant-wide settings.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-check"></i></div>
                        <h5>Clients</h5>
                        <p>Self-serve portal for order status, support tickets, and project visibility.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mkt-cta-band text-center">
            <div class="container">
                <h2>Ready to see it live?</h2>
                <p>Start your free trial or compare plans — no credit card required.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('pricing.get') }}" class="btn btn-light btn-lg fw-bold px-4">View pricing</a>
                    <a href="{{ route('contact-us.get') }}" class="btn btn-outline-light btn-lg px-4">Contact sales</a>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', ['limit' => 4])
    </div>
@endsection
