@extends('front.layout.layout')



@section('title', 'About Us')



@section('seo_title', 'About Ledrix CRM — Multi-Tenant Sales CRM for Agencies')

@section('meta_description', 'Learn about Ledrix CRM — a multi-tenant sales CRM built for agencies and closers. Meet founder Zeeshan Asghar and our vision for pipeline management, payments, and seller-first CRM software.')

@section('meta_keywords', 'About Ledrix, Ledrix CRM company, Zeeshan Asghar, CRM founder, sales CRM about, multi-tenant CRM platform, future tech CRM, agency CRM software')

@section('og_type', 'profile')



@push('styles')

    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">

@endpush



@push('schema')

    @include('front.includes.schema-breadcrumbs', ['items' => [

        ['name' => 'Home', 'url' => route('index.get')],

        ['name' => 'About', 'url' => route('about.get')],

    ]])

    <script type="application/ld+json">

    {!! json_encode([

        '@context' => 'https://schema.org',

        '@type' => 'AboutPage',

        'name' => 'About Ledrix CRM',

        'description' => 'Company and founder story for Ledrix — multi-tenant sales CRM software.',

        'url' => route('about.get'),

        'mainEntity' => [

            '@type' => 'Person',

            'name' => config('seo.founder.name'),

            'jobTitle' => config('seo.founder.job_title'),

            'url' => config('seo.founder.linkedin'),

            'sameAs' => [config('seo.founder.linkedin')],

            'worksFor' => [

                '@type' => 'Organization',

                'name' => config('seo.organization.name'),

                'url' => config('app.url'),

            ],

            'description' => config('seo.founder.description'),

        ],

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}

    </script>

@endpush



@section('main-content')

    @php

        $founderPhoto = config('seo.founder.photo');

    @endphp



    <div class="mkt-page mkt-page-about">

        {{-- Hero --}}

        <section class="mkt-about-hero text-center">

            <div class="container mkt-about-hero-inner">

                <span class="mkt-about-eyebrow"><i class="bi bi-stars"></i> About Ledrix</span>

                <h1>Modern CRM for teams that close deals — not shuffle spreadsheets</h1>

                <p class="mkt-about-hero-lead">

                    Ledrix is a multi-tenant sales platform built for agencies and revenue teams who need structure, speed, and a workspace that sellers actually use.

                </p>

                <div class="row g-3 justify-content-center mkt-about-hero-stats">

                    <div class="col-6 col-md-4 col-lg-3">

                        <div class="mkt-about-stat-pill">

                            <i class="bi bi-shield-lock"></i>

                            <span>Tenant-isolated</span>

                        </div>

                    </div>

                    <div class="col-6 col-md-4 col-lg-3">

                        <div class="mkt-about-stat-pill">

                            <i class="bi bi-lightning-charge"></i>

                            <span>Seller-first</span>

                        </div>

                    </div>

                    <div class="col-6 col-md-4 col-lg-3">

                        <div class="mkt-about-stat-pill">

                            <i class="bi bi-graph-up-arrow"></i>

                            <span>Future-ready</span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- Mission — editorial split, no card --}}

        <section class="mkt-about-section">

            <div class="container">

                <div class="row align-items-start g-4 g-lg-5">

                    <div class="col-lg-5">

                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Our mission</span>

                        <h2 class="mkt-about-section-title mb-0">CRM that follows the real revenue path</h2>

                    </div>

                    <div class="col-lg-7 mkt-about-prose">

                        <p class="mkt-about-lead">

                            Most CRMs become bloated databases that sellers avoid. Ledrix was designed around how agencies actually sell — not how software vendors imagine they should.

                        </p>

                        <div class="mkt-about-flow">

                            <span>Capture lead</span>

                            <i class="bi bi-chevron-right"></i>

                            <span>Assign seller</span>

                            <i class="bi bi-chevron-right"></i>

                            <span>Close order</span>

                            <i class="bi bi-chevron-right"></i>

                            <span>Get paid</span>

                        </div>

                        <p class="text-secondary mb-0">

                            Every workspace is tenant-isolated — multiple brands, sellers, and clients with clear boundaries. Admins get visibility; sellers get focus; clients get transparency.

                        </p>

                    </div>

                </div>

            </div>

        </section>



        {{-- Principles — numbered showcase --}}
        <section class="mkt-about-section mkt-about-principles-section">
            <div class="container">
                <div class="row align-items-end g-4 mb-5">
                    <div class="col-lg-7">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">What we stand for</span>
                        <h2 class="mkt-about-section-title mb-2">Built on three principles</h2>
                        <p class="mkt-about-lead mb-0">
                            Every Ledrix workspace runs on the same foundation — tenant isolation, connected revenue flow, and interfaces sellers actually want to use.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <p class="text-secondary mb-0 mkt-about-principles-note">
                            <i class="bi bi-check2-circle text-primary"></i>
                            Designed for agencies, closers, and multi-brand sales teams from day one.
                        </p>
                    </div>
                </div>

                <div class="mkt-about-principles-grid">
                    @foreach ([
                        [
                            'num' => '01',
                            'icon' => 'bi-layers',
                            'tone' => 'purple',
                            'title' => 'Multi-tenant by design',
                            'text' => 'Isolated CRM workspaces per agency — your data, brands, and pipelines never mix with other tenants.',
                            'points' => ['Tenant-scoped records', 'Multi-brand under one workspace'],
                        ],
                        [
                            'num' => '02',
                            'icon' => 'bi-signpost-split',
                            'tone' => 'indigo',
                            'title' => 'Full-funnel clarity',
                            'text' => 'Leads, assignments, orders, payment links, and client portal — one connected flow, zero context switching.',
                            'points' => ['Lead → order → payment', 'Single source of truth'],
                        ],
                        [
                            'num' => '03',
                            'icon' => 'bi-person-workspace',
                            'tone' => 'green',
                            'title' => 'Seller-first UX',
                            'text' => 'Dedicated panels for closers and team leads — fast, focused, and aligned with how sales work gets done.',
                            'points' => ['Role-based panels', 'Less admin, more closing'],
                        ],
                    ] as $principle)
                        <article class="mkt-about-principle mkt-about-principle--{{ $principle['tone'] }}">
                            <div class="mkt-about-principle-top">
                                <span class="mkt-about-principle-num">{{ $principle['num'] }}</span>
                                <div class="mkt-about-principle-icon mkt-about-icon--{{ $principle['tone'] }}">
                                    <i class="bi {{ $principle['icon'] }}"></i>
                                </div>
                            </div>
                            <h3>{{ $principle['title'] }}</h3>
                            <p>{{ $principle['text'] }}</p>
                            <ul class="mkt-about-principle-points">
                                @foreach ($principle['points'] as $point)
                                    <li><i class="bi bi-check-lg"></i> {{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>



        {{-- Vision — list layout, no cards --}}

        <section class="mkt-about-section">

            <div class="container">

                <div class="row mb-5">

                    <div class="col-lg-8 mx-auto text-center">

                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Vision</span>

                        <h2 class="mkt-about-section-title">A future-tech company — not just another CRM</h2>

                        <p class="text-secondary mb-0">

                            Ledrix combines SaaS fundamentals today with the automation and intelligence sales teams will expect tomorrow.

                        </p>

                    </div>

                </div>

                <div class="row justify-content-center">

                    <div class="col-lg-10">

                        <ul class="mkt-about-vision-list list-unstyled mb-0">

                            <li class="mkt-about-vision-item">

                                <div class="mkt-about-vision-icon"><i class="bi bi-cpu"></i></div>

                                <div>

                                    <h3>AI-ready pipeline</h3>

                                    <p>Lead prediction, churn signals, and smart routing — native to the platform architecture.</p>

                                </div>

                            </li>

                            <li class="mkt-about-vision-item">

                                <div class="mkt-about-vision-icon"><i class="bi bi-diagram-3"></i></div>

                                <div>

                                    <h3>Composable modules</h3>

                                    <p>Upwork workflows, milestones, performance bonuses, and API access — scale as you grow.</p>

                                </div>

                            </li>

                            <li class="mkt-about-vision-item">

                                <div class="mkt-about-vision-icon"><i class="bi bi-globe2"></i></div>

                                <div>

                                    <h3>Global infrastructure</h3>

                                    <p>Stripe, PayPal, webhooks, custom domains, and white-label — built for agencies worldwide.</p>

                                </div>

                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </section>



        {{-- Our Story — founder narrative --}}
        <section class="mkt-about-section mkt-about-story-section" id="founder">
            <div class="container">
                <div class="row align-items-center g-5 g-lg-6">
                    <div class="col-lg-6 order-lg-2">
                        <div class="mkt-founder-scene">
                            <div class="mkt-founder-scene-backdrop" aria-hidden="true"></div>
                            <figure class="mkt-founder-scene-photo">
                                @if ($founderPhoto)
                                    <img src="{{ asset($founderPhoto) }}"
                                        alt="{{ config('seo.founder.name') }} — Founder and CEO of Ledrix CRM"
                                        width="560" height="700" loading="lazy">
                                @else
                                    <div class="mkt-founder-photo-fallback" aria-hidden="true">
                                        <span>ZA</span>
                                    </div>
                                @endif
                            </figure>
                            <div class="mkt-founder-scene-accent" aria-hidden="true">
                                <span class="mkt-founder-scene-coffee"><i class="bi bi-cup-hot"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 order-lg-1">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Our story</span>
                        <h2 class="mkt-about-section-title">Built for teams that sell — not shuffle tools</h2>

                        <div class="mkt-about-story-prose">
                            <p>{{ config('seo.founder.story.origin') }}</p>
                            <p>{{ config('seo.founder.story.founding') }}</p>
                            <p class="mb-0">{{ config('seo.founder.story.today') }}</p>
                        </div>

                        <div class="mkt-about-founder-profile">
                            <h3 class="mkt-about-founder-name mb-1">{{ config('seo.founder.name') }}</h3>
                            <p class="mkt-about-founder-title">{{ config('seo.founder.job_title') }}, {{ config('seo.organization.name') }}</p>
                        </div>

                        <div class="mkt-about-founder-actions">
                            <a href="{{ config('seo.founder.linkedin') }}"
                                class="btn mkt-about-btn-primary"
                                target="_blank"
                                rel="noopener noreferrer me author">
                                <i class="bi bi-linkedin"></i> Connect on LinkedIn
                            </a>
                            <a href="{{ route('contact-us.get') }}" class="btn mkt-about-btn-outline">
                                <i class="bi bi-envelope"></i> Contact team
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        {{-- CTA --}}

        <section class="mkt-cta-band">

            <div class="container text-center">

                <h2>Ready to see Ledrix in your workflow?</h2>

                <p class="mb-4 mx-auto mkt-cta-lead">

                    Start a 14-day free trial or talk to us about enterprise setup — no credit card required.

                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3">

                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">View plans</a>

                    <a href="{{ route('features.get') }}" class="btn btn-lg mkt-btn-ghost">Explore features</a>

                </div>

            </div>

        </section>

    </div>

@endsection

