@extends('front.layout.layout')

@section('title', 'Bank transfer instructions')

@section('robots', 'noindex, nofollow')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/tenant-billing.css') }}">
@endpush

@section('main-content')
    <div class="tenant-billing-page">
        <header class="hero d-flex align-items-center justify-content-center text-center"
            style="background: linear-gradient(rgba(30,27,75,.72), rgba(68,56,201,.65)), url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center; background-size: cover;">
            <div class="container text-white">
                <h1>Complete your bank transfer</h1>
                <p class="mb-0">{{ $tenant->name }}</p>
            </div>
        </header>

        <main class="py-5">
            <div class="container">
                <div class="mb-4">
                    <a href="{{ route('tenant.billing') }}" class="text-muted small text-decoration-none">&larr; Back to billing</a>
                    <h4 class="mb-0 mt-1">Bank transfer — scan QR</h4>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="billing-card">
                            <div class="billing-card__head billing-card__head--muted">
                                <i class="bi bi-bank me-1"></i> Transfer details
                            </div>
                            <div class="billing-card__body">
                                @include('front.pages.tenant.partials.bank-transfer-instructions', [
                                    'tenant' => $tenant,
                                    'payment' => $payment,
                                    'invoice' => $invoice,
                                    'bank' => $bank,
                                    'qrDataUri' => $qrDataUri ?? null,
                                    'qrError' => $qrError ?? null,
                                    'showReportForm' => true,
                                ])
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('tenant.billing') }}" class="btn btn-outline-primary">
                                Return to billing
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection
