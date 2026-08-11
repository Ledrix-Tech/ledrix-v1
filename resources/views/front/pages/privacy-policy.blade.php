@extends('front.layout.layout')

@section('title', 'Privacy Policy')

@section('seo_title', 'Privacy Policy — Ledrix CRM')
@section('meta_description', 'How Ledrix CRM collects, uses, and protects personal data for website visitors, trial signups, and customer workspaces.')
@section('meta_keywords', 'Ledrix privacy, CRM privacy policy, Ledrix data protection, SaaS privacy')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Privacy Policy', 'url' => route('privacy.get')],
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
                            <span>Privacy Policy</span>
                        </nav>
                        <div class="mkt-legal-hero-row">
                            <div>
                                <span class="mkt-legal-kicker"><i class="bi bi-shield-lock"></i> Legal</span>
                                <h1>Privacy Policy</h1>
                                <p class="mkt-legal-lead">How {{ $company }} collects, uses, and protects information when you use Ledrix CRM.</p>
                            </div>
                            <div class="mkt-legal-updated">
                                <span class="mkt-legal-updated-label">Last updated</span>
                                <strong>{{ $updated }}</strong>
                            </div>
                        </div>
                        <div class="mkt-legal-switch" role="tablist" aria-label="Legal documents">
                            <a href="{{ route('terms.get') }}">Terms of Service</a>
                            <a href="{{ route('privacy.get') }}" class="is-active" aria-current="page">Privacy Policy</a>
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
                                <h2><span class="mkt-legal-num">01</span> Who we are</h2>
                                <p>
                                    {{ $company }} (“we”, “us”) operates Ledrix CRM and the website at ledrix.co.
                                    Contact: <a href="mailto:{{ $email }}">{{ $email }}</a>.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">02</span> Scope</h2>
                                <p>This Policy covers:</p>
                                <ul>
                                    <li>Visitors to our marketing site and landing pages</li>
                                    <li>People who request demos, contact us, or start a trial</li>
                                    <li>Administrators, sellers, and clients using a Ledrix workspace</li>
                                </ul>
                                <p>
                                    If you use Ledrix to store your own customers’ data, you are the controller of that Customer Data;
                                    we process it on your behalf to provide the Service. You must provide your own notices to your end users where required.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">03</span> Information we collect</h2>
                                <div class="mkt-legal-grid">
                                    <div class="mkt-legal-card">
                                        <h3>Account &amp; billing</h3>
                                        <ul>
                                            <li>Name, email, company name, password (stored hashed)</li>
                                            <li>Plan, trial dates, subscription and payment status</li>
                                            <li>Invoices and payment references from processors you use</li>
                                        </ul>
                                    </div>
                                    <div class="mkt-legal-card">
                                        <h3>Workspace content</h3>
                                        <ul>
                                            <li>Leads, clients, orders, tickets, briefs, and related CRM records you or your users create</li>
                                        </ul>
                                    </div>
                                    <div class="mkt-legal-card">
                                        <h3>Usage &amp; technical</h3>
                                        <ul>
                                            <li>IP address, browser type, device, pages viewed, approximate location derived from IP</li>
                                            <li>Logs needed for security, debugging, and reliability</li>
                                            <li>Cookies for session, CSRF protection, and preferences</li>
                                        </ul>
                                    </div>
                                    <div class="mkt-legal-card">
                                        <h3>Marketing &amp; ads</h3>
                                        <ul>
                                            <li>Meta Pixel / Google tags may collect page views and conversion events</li>
                                            <li>Domain verification and advertising identifiers when configured</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">04</span> How we use information</h2>
                                <ul>
                                    <li>Provide, secure, and improve the Service</li>
                                    <li>Create and manage tenant workspaces, trials, and subscriptions</li>
                                    <li>Send transactional email (verification, billing, security alerts)</li>
                                    <li>Respond to support and demo requests</li>
                                    <li>Measure marketing performance and improve our website (where tags are enabled)</li>
                                    <li>Comply with law and enforce our <a href="{{ route('terms.get') }}">Terms of Service</a></li>
                                </ul>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">05</span> Legal bases</h2>
                                <p>
                                    Depending on your location, we rely on contract performance, legitimate interests (security, product improvement),
                                    consent (certain cookies/marketing), and legal obligations.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">06</span> Sharing</h2>
                                <p>We share data only as needed with:</p>
                                <ul>
                                    <li>Infrastructure and email providers that host or send on our behalf</li>
                                    <li>Payment gateways you choose for SaaS billing or customer payment links</li>
                                    <li>Advertising platforms when pixels/tags are active on our marketing pages</li>
                                    <li>Authorities when required by law</li>
                                </ul>
                                <p class="mkt-legal-callout">We do not sell your personal information.</p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">07</span> International transfers &amp; tenancy</h2>
                                <p>
                                    Data may be processed in countries where we or our processors operate.
                                    Customer Data in the CRM is scoped by tenant so other customers cannot access your workspace.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">08</span> Retention</h2>
                                <p>
                                    We retain account and billing records for as long as your workspace exists and as needed for legal, tax, and dispute purposes.
                                    After cancellation or deletion requests, we delete or anonymize Customer Data within a reasonable period,
                                    except where we must retain it by law or for legitimate security logs.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">09</span> Security</h2>
                                <p>
                                    We use industry-standard measures such as encrypted transport (HTTPS), hashed passwords, tenant isolation,
                                    and access controls. No method of transmission or storage is 100% secure.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">10</span> Your rights</h2>
                                <p>
                                    Depending on applicable law, you may request access, correction, deletion, or export of personal data we hold about you,
                                    or object to certain processing. Workspace owners can manage much of their CRM data directly in the product.
                                    Contact <a href="mailto:{{ $email }}">{{ $email }}</a> for privacy requests.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">11</span> Children</h2>
                                <p>Ledrix is not directed to children under 16. We do not knowingly collect data from children.</p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">12</span> Changes</h2>
                                <p>
                                    We may update this Policy and will post the new version with a revised “Last updated” date.
                                    Significant changes may also be communicated by email or in-product notice.
                                </p>
                            </div>

                            <div class="mkt-legal-section mkt-legal-section--last">
                                <h2><span class="mkt-legal-num">13</span> Contact</h2>
                                <p>
                                    Privacy questions: <a href="mailto:{{ $email }}">{{ $email }}</a>
                                    · <a href="{{ route('contact-us.get') }}">Contact form</a>
                                    · <a href="{{ route('terms.get') }}">Terms of Service</a>
                                </p>
                            </div>
                        </article>

                        <div class="mkt-legal-footer-nav">
                            <a href="{{ route('terms.get') }}" class="mkt-legal-next">
                                <span>Previous</span>
                                <strong><i class="bi bi-arrow-left"></i> Terms of Service</strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
