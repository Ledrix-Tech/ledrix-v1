@extends('front.layout.layout')

@section('title', 'Home')

@section('seo_title', 'Ledrix CRM — Multi-Tenant Sales CRM Software for Agencies')
@section('meta_description', 'Ledrix CRM helps agencies and sales teams capture leads, assign sellers, manage orders, and collect payments. Multi-tenant, seller panels, client portal, Stripe & PayPal — start a free trial.')
@section('meta_keywords', 'Ledrix, Ledrix CRM, CRM software, sales CRM, multi-tenant CRM, agency CRM, lead management, pipeline CRM, SaaS CRM, customer relationship management')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
    ]])
    @include('front.includes.schema-faq', ['faqs' => array_slice(config('seo.faq', []), 0, 5)])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page">
        {{-- Hero --}}
        <section class="mkt-hero text-center">
            <div class="container mkt-hero-inner">
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> Multi-tenant sales CRM</span>
                <h1>Ledrix CRM — Capture leads. Close deals. Scale your agency.</h1>
                <p class="mkt-hero-lead">
                    Ledrix gives sales teams a complete workspace — lead intake, seller assignment, payments, and client portal — with tenant-isolated data from day one.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">Start free trial</a>
                    <a href="{{ route('contact-us.get') }}" class="btn btn-lg mkt-btn-ghost">Talk to sales</a>
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                    <span><i class="bi bi-shield-lock"></i> Tenant-isolated CRM</span>
                    <span><i class="bi bi-box-arrow-in-right"></i> One-click admin access</span>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section class="mkt-section mkt-section-alt">
            <div class="container text-center">
                <h2 class="mkt-section-title">How Ledrix works</h2>
                <p class="mkt-section-lead">
                    From first touch to paid client — one platform for agencies and sales teams that need structure without complexity.
                </p>
                <div class="mkt-grid-3">
                    <div class="mkt-card text-start">
                        <span class="mkt-step-num">1</span>
                        <h5>Capture leads</h5>
                        <p>Ingest leads via API, webhooks, or manual entry. Assign to sellers and brands automatically based on your rules.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <span class="mkt-step-num">2</span>
                        <h5>Work the pipeline</h5>
                        <p>Track assignments, follow-ups, orders, and payment links. Sellers get their panel; admins get full visibility.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <span class="mkt-step-num">3</span>
                        <h5>Get paid & retain</h5>
                        <p>Stripe and PayPal payment links, client portal access, and performance tracking — all scoped to your workspace.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- @php($launchVideo = config('seo.launch_video', []))
        @if (! empty($launchVideo['file']))
        {{-- Video — SaaS V1 launch audit (Admin + Seller) --}}
        <section class="mkt-video-section">
            <div class="container text-center">
                <h2 class="mkt-section-title mb-2">See Ledrix in action</h2>
                <p class="text-muted mb-2">
                    Full SaaS V1 audit — Admin and Seller workspaces on {{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'ledrix.co' }}.
                </p>
                <p class="small text-muted mb-4">~10 minutes · English captions included</p>
                <div class="mkt-video-wrapper" id="mktVideoWrapper">
                    <img class="mkt-video-thumb"
                        src="{{ asset($launchVideo['poster'] ?? 'front-assets/media/ledrix-crm-audit-v1-thumb.jpg') }}"
                        alt="Ledrix CRM V1 audit — admin and seller product demo">
                    <div class="mkt-play-btn" id="mktPlayBtn" role="button" aria-label="Play Ledrix CRM V1 audit video">
                        <span><i class="bi bi-play-fill"></i></span>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <a href="{{ asset($launchVideo['file']) }}"
                        class="btn mkt-btn-primary"
                        download="{{ $launchVideo['download_name'] ?? 'Ledrix-CRM-SaaS-V1-Audit.mp4' }}">
                        <i class="bi bi-download"></i> Download SaaS V1 audit (MP4)
                    </a>
                    @if (! empty($launchVideo['download_full']))
                        <a href="{{ asset($launchVideo['download_full']) }}"
                            class="btn mkt-btn-ghost"
                            download="{{ $launchVideo['download_name'] ?? 'Ledrix-CRM-SaaS-V1-Audit.mp4' }}">
                            <i class="bi bi-box-arrow-down"></i> Full quality (HD)
                        </a>
                    @endif
                </div>
            </div>
            <div class="mkt-video-modal" id="mktVideoModal">
                <button type="button" class="mkt-video-close" id="mktVideoClose" aria-label="Close video">&times;</button>
                <video id="mktModalVideo" controls crossorigin="anonymous" playsinline preload="metadata"
                    title="{{ $launchVideo['title'] ?? 'Ledrix CRM V1 walkthrough' }}">
                    <source src="{{ asset($launchVideo['file']) }}" type="video/mp4">
                    @if (! empty($launchVideo['captions']))
                        <track kind="captions" src="{{ asset($launchVideo['captions']) }}"
                            srclang="en" label="English" default>
                    @endif
                    Your browser does not support HTML5 video.
                </video>
            </div>
        </section>
        @endif -->

        {{-- Use cases --}}
        <section class="mkt-section mkt-section-muted">
            <div class="container text-center">
                <h2 class="mkt-section-title">Built for how you sell</h2>
                <p class="mkt-section-lead">Whether you're a solo closer or a multi-brand agency, Ledrix adapts to your workflow.</p>
                <div class="mkt-grid-3">
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-rocket-takeoff"></i></div>
                        <h5>Startups & closers</h5>
                        <p>Launch fast with lead tracking, seller panels, and payment links — no heavy setup or IT team required.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-building"></i></div>
                        <h5>Agencies</h5>
                        <p>Run multiple brands under one tenant. Separate pipelines, shared reporting, and role-based access for every seller.</p>
                    </div>
                    <div class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h5>Growing teams</h5>
                        <p>Scale sellers, leads, and clients with plan-based limits. Upgrade when you're ready — trial first, pay later.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Testimonial --}}
        <section class="mkt-testimonial text-center">
            <div class="container">
                <h2 class="h4 fw-bold mb-4">Trusted by sales-driven teams</h2>
                <div class="mkt-quote-card">
                    <blockquote class="mb-0">"We cut manual follow-ups in half and got every seller on the same pipeline within a week of switching to Ledrix."</blockquote>
                    <footer class="small opacity-75 mt-3">— Agency operations lead</footer>
                </div>
            </div>
        </section>

        {{-- SEO: product summary --}}
        <section class="mkt-section" aria-labelledby="why-ledrix-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <h2 class="mkt-section-title" id="why-ledrix-heading">Why agencies choose Ledrix CRM software</h2>
                        <p class="mkt-section-lead">
                            Ledrix is a multi-tenant sales CRM built for agencies, closers, and revenue teams that need more than a contact list.
                            Capture inbound leads, route them to the right seller, convert to orders, send Stripe or PayPal payment links, and give clients a secure portal — all inside one tenant-isolated workspace.
                        </p>
                    </div>
                </div>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <div class="mkt-card text-start h-100">
                            <h3 class="h5">Lead management CRM</h3>
                            <p class="mb-0 small text-secondary">API intake, seller assignment, brand routing, and pipeline visibility — purpose-built for high-volume sales teams.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mkt-card text-start h-100">
                            <h3 class="h5">Payments &amp; orders</h3>
                            <p class="mb-0 small text-secondary">Payment links, milestone billing, order tracking, and chargeback handling tied directly to your CRM records.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mkt-card text-start h-100">
                            <h3 class="h5">Multi-tenant SaaS</h3>
                            <p class="mb-0 small text-secondary">Each customer workspace is isolated by tenant ID — your brands, sellers, and client data never mix with other organizations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', ['limit' => 5])

        {{-- Trial CTA --}}
        <section class="mkt-cta-band" id="trial">
            <div class="container text-center">
                <h2>Start your 14-day free trial</h2>
                <p class="mb-4 mx-auto" style="max-width: 560px;">
                    Pick a plan, create your workspace, and explore the full CRM — no credit card required.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">View plans &amp; register</a>
                    <a href="{{ route('contact-us.get') }}" class="btn btn-lg mkt-btn-ghost">Questions? Contact us</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/marketing.js') }}" defer></script>
@endpush
