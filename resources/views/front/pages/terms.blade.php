@extends('front.layout.layout')

@section('title', 'Terms of Service')

@section('seo_title', 'Terms of Service — Ledrix CRM')
@section('meta_description', 'Read the Ledrix CRM Terms of Service covering accounts, trials, subscriptions, acceptable use, and your responsibilities when using our multi-tenant sales CRM.')
@section('meta_keywords', 'Ledrix terms, CRM terms of service, Ledrix CRM legal, SaaS terms')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Terms of Service', 'url' => route('terms.get')],
    ]])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    @php
        $company = config('seo.organization.name', 'Ledrix');
        $email = config('seo.organization.email', 'hello@ledrix.co');
        $updated = 'August 8, 2026';
    @endphp

    <div class="mkt-page mkt-page-legal">
        <section class="mkt-legal-hero">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <nav class="mkt-legal-crumbs" aria-label="Breadcrumb">
                            <a href="{{ route('index.get') }}">Home</a>
                            <span aria-hidden="true">/</span>
                            <span>Terms of Service</span>
                        </nav>
                        <div class="mkt-legal-hero-row">
                            <div>
                                <span class="mkt-legal-kicker"><i class="bi bi-file-earmark-text"></i> Legal</span>
                                <h1>Terms of Service</h1>
                                <p class="mkt-legal-lead">The agreement between you and {{ $company }} for using Ledrix CRM.</p>
                            </div>
                            <div class="mkt-legal-updated">
                                <span class="mkt-legal-updated-label">Last updated</span>
                                <strong>{{ $updated }}</strong>
                            </div>
                        </div>
                        <div class="mkt-legal-switch" role="tablist" aria-label="Legal documents">
                            <a href="{{ route('terms.get') }}" class="is-active" aria-current="page">Terms of Service</a>
                            <a href="{{ route('privacy.get') }}">Privacy Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-legal-body">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <article class="mkt-legal">
                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">01</span> Agreement</h2>
                                <p>
                                    By accessing or using Ledrix CRM (the “Service”), including free trials, paid subscriptions,
                                    admin, seller, and client portals, you agree to these Terms of Service (“Terms”).
                                    If you are registering on behalf of a company, you represent that you have authority to bind that organization.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">02</span> The Service</h2>
                                <p>
                                    Ledrix is a multi-tenant sales CRM for agencies and sales teams. Features may include lead intake,
                                    seller assignment, orders, payment links, client portals, and related workspace tools, subject to
                                    your plan, trial status, and enabled features.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">03</span> Accounts &amp; workspaces</h2>
                                <ul>
                                    <li>You must provide accurate registration and billing contact information.</li>
                                    <li>You are responsible for safeguarding login credentials and for activity under your workspace.</li>
                                    <li>Each tenant workspace is isolated; you may not attempt to access another tenant’s data.</li>
                                    <li>You must keep admin, seller, and client users within the limits of your plan.</li>
                                </ul>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">04</span> Trials &amp; billing</h2>
                                <p>
                                    Free trials (when offered) provide access for a limited period, typically without requiring a card up front.
                                    When a trial or paid period ends, access may be restricted until you renew or upgrade.
                                    Fees, taxes, refunds, and payment methods are described at checkout and in your organization billing screens.
                                    Unpaid or expired subscriptions may suspend CRM access while preserving data according to our retention practices.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">05</span> Acceptable use</h2>
                                <p>You agree not to:</p>
                                <ul>
                                    <li>Violate laws, spam, or process unlawful content through the Service</li>
                                    <li>Probe, abuse, or disrupt infrastructure, or reverse-engineer the platform beyond legal rights</li>
                                    <li>Resell or white-label the Service except where your plan expressly allows white-label features</li>
                                    <li>Upload malware or attempt to bypass authentication, billing, or feature gates</li>
                                </ul>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">06</span> Customer data</h2>
                                <p>
                                    You retain ownership of leads, clients, orders, and other content you submit (“Customer Data”).
                                    You grant {{ $company }} a limited license to host, process, and display Customer Data solely to provide the Service.
                                    You are responsible for having lawful bases to collect and process personal data you store in Ledrix
                                    (for example, your own clients and leads). Our handling of account and usage data is described in the
                                    <a href="{{ route('privacy.get') }}">Privacy Policy</a>.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">07</span> Third-party services</h2>
                                <p>
                                    The Service may integrate with payment processors (such as Stripe, PayPal, JazzCash, or bank transfer flows),
                                    email providers, and advertising pixels. Those services are governed by their own terms.
                                    {{ $company }} is not liable for outages or policy changes of third-party providers.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">08</span> Intellectual property</h2>
                                <p>
                                    Ledrix software, branding, and documentation remain the property of {{ $company }} and its licensors.
                                    These Terms do not transfer ownership of our IP to you.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">09</span> Disclaimer &amp; limitation of liability</h2>
                                <p>
                                    The Service is provided “as is” to the fullest extent permitted by law.
                                    {{ $company }} does not warrant uninterrupted or error-free operation.
                                    To the maximum extent allowed, {{ $company }}’s total liability arising from the Service is limited to
                                    the fees you paid for the Service in the three (3) months before the claim.
                                    We are not liable for indirect, incidental, or consequential damages, including lost profits or data,
                                    except where liability cannot be excluded by law.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">10</span> Suspension &amp; termination</h2>
                                <p>
                                    We may suspend or terminate access for breach of these Terms, non-payment, abuse, or legal risk.
                                    You may stop using the Service at any time; cancellation and data export options (if available)
                                    are managed from your organization settings or by contacting support.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">11</span> Changes</h2>
                                <p>
                                    We may update these Terms. Material changes will be posted on this page with an updated date.
                                    Continued use after changes take effect constitutes acceptance.
                                </p>
                            </div>

                            <div class="mkt-legal-section mkt-legal-section--last">
                                <h2><span class="mkt-legal-num">12</span> Contact</h2>
                                <p>
                                    Questions about these Terms:
                                    <a href="mailto:{{ $email }}">{{ $email }}</a>
                                    or use our <a href="{{ route('contact-us.get') }}">contact form</a>.
                                </p>
                            </div>
                        </article>

                        <div class="mkt-legal-footer-nav">
                            <a href="{{ route('privacy.get') }}" class="mkt-legal-next">
                                <span>Next</span>
                                <strong>Privacy Policy <i class="bi bi-arrow-right"></i></strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
