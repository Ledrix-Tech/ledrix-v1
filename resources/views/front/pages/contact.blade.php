@extends('front.layout.layout')

@section('title', 'Contact')

@section('seo_title', 'Contact Ledrix CRM — Sales & Support')
@section('meta_description', 'Contact the Ledrix CRM team for pricing, enterprise plans, demos, and onboarding help. We respond within one business day.')
@section('meta_keywords', 'Contact Ledrix, Ledrix CRM support, CRM demo, sales CRM contact, agency CRM inquiry')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Contact', 'url' => route('contact-us.get')],
    ]])
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Contact Ledrix CRM',
        'url' => route('contact-us.get'),
        'description' => 'Contact Ledrix CRM for sales, support, and enterprise onboarding.',
        'mainEntity' => [
            '@type' => 'Organization',
            'name' => config('seo.organization.name'),
            'url' => config('app.url'),
            'email' => config('seo.organization.email'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => config('seo.organization.email'),
                'availableLanguage' => ['English'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page mkt-page-contact">
        {{-- Hero --}}
        <section class="mkt-hero mkt-hero-contact text-center">
            <div class="container mkt-hero-inner px-3 px-sm-4">
                <span class="mkt-hero-badge"><i class="bi bi-chat-dots"></i> We're here to help</span>
                <h1>Contact Ledrix CRM — sales &amp; support</h1>
                <p class="mkt-hero-lead">
                    Questions about Ledrix CRM pricing, enterprise plans, onboarding, or multi-tenant setup?
                    Send us a message — we typically respond within one business day.
                </p>
                <a href="#contactForm" class="btn btn-lg mkt-btn-primary">Send a message</a>
            </div>
        </section>

        {{-- Contact form --}}
        <section class="mkt-contact-shell">
            <div class="container px-3 px-sm-4">
                <div class="row g-4 align-items-start">
                    {{-- Form --}}
                    <div class="col-lg-7">
                        <div class="mkt-contact-card">
                            <h2>Talk to our team</h2>
                            <p class="text-muted mb-4">Fill out the form below and we'll get back to you within one business day.</p>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0 small">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form id="contactForm" class="mkt-contact-form" action="{{ route('contact.store') }}" method="POST">
                                @csrf

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="contact-name">Full name *</label>
                                        <input type="text" id="contact-name" name="name"
                                            value="{{ old('name') }}"
                                            class="form-control mkt-form-control @error('name') is-invalid @enderror"
                                            placeholder="John Doe" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="contact-company">Company name</label>
                                        <input type="text" id="contact-company" name="company"
                                            value="{{ old('company') }}"
                                            class="form-control mkt-form-control"
                                            placeholder="Acme Agency">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="contact-email">Business email *</label>
                                        <input type="email" id="contact-email" name="email"
                                            value="{{ old('email') }}"
                                            class="form-control mkt-form-control @error('email') is-invalid @enderror"
                                            placeholder="you@company.com" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="phone">Phone / WhatsApp</label>
                                        <div class="mkt-phone-field">
                                            <input type="tel" id="phone" name="phone"
                                                value="{{ old('phone') }}"
                                                class="form-control mkt-form-control"
                                                placeholder="Enter your phone number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="company-size">Company size</label>
                                        <select id="company-size" name="company_size" class="form-select mkt-form-control">
                                            <option value="">Select</option>
                                            @foreach (['1-10' => '1 – 10 employees', '11-50' => '11 – 50 employees', '51-200' => '51 – 200 employees', '200+' => '200+ employees'] as $val => $label)
                                                <option value="{{ $val }}" @selected(old('company_size') === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="mkt-form-label" for="inquiry-type">Inquiry type *</label>
                                        <select id="inquiry-type" name="inquiry_type"
                                            class="form-select mkt-form-control @error('inquiry_type') is-invalid @enderror" required>
                                            @foreach (['pricing' => 'Pricing & trial', 'sales' => 'Sales inquiry', 'partnership' => 'Partnership', 'support' => 'Technical support', 'general' => 'General inquiry'] as $val => $label)
                                                <option value="{{ $val }}" @selected(old('inquiry_type', 'pricing') === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('inquiry_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="mkt-form-label" for="contact-message">Message *</label>
                                        <textarea id="contact-message" name="message" rows="5"
                                            class="form-control mkt-form-control @error('message') is-invalid @enderror"
                                            placeholder="Tell us about your business requirements..." required>{{ old('message') }}</textarea>
                                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary mkt-submit-btn">
                                            <i class="bi bi-send me-1"></i> Send inquiry
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-5 mkt-contact-sidebar">
                        <div class="mkt-contact-card">
                            <h4 class="fw-bold mb-3">Why Ledrix?</h4>
                            @foreach (['CRM dashboard & pipeline', 'Lead & seller management', 'Orders & payment links', 'Client & admin portals', 'Multi-tenant SaaS architecture', 'Secure cloud platform'] as $item)
                                <div class="mkt-sidebar-feature"><i class="bi bi-check-circle-fill"></i> {{ $item }}</div>
                            @endforeach
                        </div>

                        <div class="mkt-contact-card">
                            <h4 class="fw-bold mb-3">Contact information</h4>
                            <div class="mkt-info-item">
                                <div class="mkt-info-icon"><i class="bi bi-envelope"></i></div>
                                <div>
                                    <strong>Email</strong>
                                    <span>info@ledrix.co</span>
                                </div>
                            </div>
                            <div class="mkt-info-item">
                                <div class="mkt-info-icon"><i class="bi bi-clock"></i></div>
                                <div>
                                    <strong>Response time</strong>
                                    <span>Within 24 hours</span>
                                </div>
                            </div>
                            <hr class="my-3">
                            <h5 class="fw-bold mb-2">Prefer self-serve?</h5>
                            <p class="text-muted small mb-3">Start a free trial instantly — no sales call required.</p>
                            <a href="{{ route('pricing.get') }}" class="btn btn-outline-primary w-100">Start free trial</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('head')
    @include('front.includes.phone-input-styles')
@endpush

@push('scripts')
    @include('front.includes.phone-input-assets')
@endpush
