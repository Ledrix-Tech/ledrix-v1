@extends('front.layout.lp')

@section('title', 'Demo request received')

@section('seo_title', 'Thanks — Ledrix demo request received')
@section('meta_description', 'Your Ledrix CRM demo request was received. Our team will contact you shortly.')
@section('robots', 'noindex, nofollow')

@section('main-content')
    <div class="mkt-page lp-page">
        <section class="lp-thanks-wrap text-center">
            <div class="container px-3 px-sm-4" style="max-width: 560px;">
                <div class="lp-thanks-icon" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
                <h1 class="mkt-section-title">You’re on the list</h1>
                <p class="mkt-section-lead mx-auto mb-4">
                    Thanks — we received your demo request. A Ledrix teammate will email you within one business day.
                </p>
                <div class="mkt-hero-actions justify-content-center">
                    <a href="{{ route('lp.trial') }}" class="btn btn-lg mkt-btn-primary">Start a free trial</a>
                    <a href="{{ route('index.get') }}" class="btn btn-lg mkt-btn-secondary">Back to home</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @include('front.includes.analytics-conversion', ['event' => 'Lead'])
@endpush
