@extends('front.layout.layout')

@section('title', 'FAQ')

@section('seo_title', 'Ledrix CRM FAQ — Sales CRM, Trials & Multi-Tenant Workspaces')
@section('meta_description', 'Answers to common questions about Ledrix CRM: what it is, free trial, lead management, payments, tenant isolation, and how agencies use Ledrix to scale sales.')
@section('meta_keywords', 'Ledrix FAQ, CRM FAQ, sales CRM questions, Ledrix CRM help, multi-tenant CRM FAQ, agency CRM software, CRM free trial, customer relationship management FAQ')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'FAQ', 'url' => route('faq.get')],
    ]])
    @include('front.includes.schema-faq')
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page">
        <section class="mkt-hero text-center">
            <div class="container mkt-hero-inner">
                <span class="mkt-hero-badge"><i class="bi bi-question-circle-fill"></i> Help center</span>
                <h1>Ledrix CRM — frequently asked questions</h1>
                <p class="mkt-hero-lead">
                    Everything you need to know about Ledrix sales CRM, free trials, multi-tenant workspaces, and getting your agency pipeline live.
                </p>
            </div>
        </section>

        @include('front.includes.faq-section')

        <section class="mkt-cta-band">
            <div class="container text-center">
                <h2>Still have questions?</h2>
                <p class="mb-4 mx-auto" style="max-width: 520px;">
                    Talk to our team about pricing, onboarding, or enterprise CRM setup.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('contact-us.get') }}" class="btn btn-lg mkt-btn-primary">Contact us</a>
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-ghost">View pricing</a>
                </div>
            </div>
        </section>
    </div>
@endsection
