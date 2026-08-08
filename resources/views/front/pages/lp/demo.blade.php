@extends('front.layout.lp')

@section('title', 'Book a demo')

@section('seo_title', 'Book a Ledrix CRM Demo — See Agency Sales CRM in Action')
@section('meta_description', 'Request a Ledrix CRM demo. See lead capture, seller assignment, payments, and client portal — built for agencies and closers.')
@section('robots', 'noindex, follow')

@section('main-content')
    <div class="mkt-page mkt-page-contact lp-page">
        <section class="mkt-hero mkt-hero-contact text-center">
            <div class="container mkt-hero-inner px-3 px-sm-4">
                <span class="mkt-hero-badge"><i class="bi bi-calendar2-check"></i> 20-minute product walkthrough</span>
                <h1>See how agencies capture more leads with Ledrix</h1>
                <p class="mkt-hero-lead">
                    Book a short demo — lead intake, seller panels, payment links, and client portal.
                    We typically reply within one business day.
                </p>
                <a href="#demoForm" class="btn btn-lg mkt-btn-primary">Request a demo</a>
            </div>
        </section>

        <section class="mkt-contact-shell">
            <div class="container px-3 px-sm-4">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-7">
                        <div class="mkt-contact-card">
                            <h2>Request your demo</h2>
                            <p class="text-muted mb-4">Tell us about your team — we’ll follow up with next steps. Requests also appear in Super Admin → Demo Requests.</p>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0 small">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form id="demoForm" class="mkt-contact-form" method="POST" action="{{ route('demo.store') }}">
                                @csrf
                                <input type="hidden" name="source" value="lp_demo">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="name">Full name</label>
                                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                                            class="form-control mkt-form-control @error('name') is-invalid @enderror"
                                            required maxlength="255" autocomplete="name">
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Work email</label>
                                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                                            class="form-control mkt-form-control @error('email') is-invalid @enderror"
                                            required maxlength="255" autocomplete="email">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="company">Company <span class="text-muted fw-normal">(optional)</span></label>
                                        <input id="company" type="text" name="company" value="{{ old('company') }}"
                                            class="form-control mkt-form-control @error('company') is-invalid @enderror"
                                            maxlength="255" autocomplete="organization">
                                        @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="description">What are you looking to solve?</label>
                                        <textarea id="description" name="description" rows="4"
                                            class="form-control mkt-form-control @error('description') is-invalid @enderror"
                                            maxlength="5000"
                                            placeholder="e.g. lead routing for 5 closers, payment links, client portal">{{ old('description') }}</textarea>
                                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-lg mkt-btn-primary w-100">Request demo</button>
                                        <p class="small text-muted mt-3 mb-0 text-center">
                                            Prefer self-serve? <a href="{{ route('lp.trial') }}">Start a free trial</a>
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="mkt-contact-sidebar">
                            <div class="mkt-contact-card">
                                <div class="mkt-info-item">
                                    <div class="mkt-info-icon"><i class="bi bi-funnel"></i></div>
                                    <div>
                                        <strong>Lead → seller → paid client</strong>
                                        <span>See the full pipeline without tool-switching.</span>
                                    </div>
                                </div>
                                <div class="mkt-info-item">
                                    <div class="mkt-info-icon"><i class="bi bi-buildings"></i></div>
                                    <div>
                                        <strong>Multi-tenant isolation</strong>
                                        <span>Separate workspaces for brands and teams.</span>
                                    </div>
                                </div>
                                <div class="mkt-info-item mb-0">
                                    <div class="mkt-info-icon"><i class="bi bi-sliders"></i></div>
                                    <div>
                                        <strong>Fit for your motion</strong>
                                        <span>Plans, limits, and the right onboarding path.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
