@extends('front.layout.layout')

@section('title', 'Billing')

@section('robots', 'noindex, nofollow')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/tenant-billing.css') }}">
@endpush

@section('main-content')
    <div class="tenant-billing-page">
        <header class="hero d-flex align-items-center justify-content-center text-center"
            style="background: linear-gradient(rgba(30,27,75,.72), rgba(68,56,201,.65)), url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center; background-size: cover;">
            <div class="container text-white">
                <h1>Billing & Subscription</h1>
                <p class="mb-0">{{ $tenant->name }}</p>
            </div>
        </header>

        <main class="py-5">
            <div class="container">
                <div class="mb-4">
                    <a href="{{ route('tenant.dashboard') }}" class="text-muted small text-decoration-none">&larr; Back to dashboard</a>
                    <h4 class="mb-0 mt-1">Manage your plan</h4>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if (request('cancelled'))
                    <div class="alert alert-warning">Payment was cancelled. You can try again below.</div>
                @endif

                @if ($expiresSoon && ! $needsPayment)
                    <div class="alert alert-info">
                        Your subscription renews in {{ $daysUntilRenewal }} day(s)
                        ({{ $membership->end_date->format('M d, Y') }}).
                        <a href="{{ route('tenant.billing') }}" class="alert-link">Renew early</a>
                        to avoid any interruption.
                    </div>
                @elseif ($needsPayment)
                    <div class="alert alert-warning">
                        Payment is required to keep CRM access.
                        Complete your renewal below.
                    </div>
                @endif

                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-5">
                        <div class="billing-card h-100">
                            <div class="billing-card__head">Current plan</div>
                            <div class="billing-card__body">
                                <div class="billing-stat">
                                    <span class="billing-stat__label">Plan</span>
                                    <span class="billing-stat__value">{{ $tenant->plan?->name ?? '—' }}</span>
                                </div>
                                <div class="billing-stat">
                                    <span class="billing-stat__label">Billing cycle</span>
                                    <span class="billing-stat__value">{{ ucfirst($membership?->billing_cycle ?? 'monthly') }}</span>
                                </div>
                                <div class="billing-stat">
                                    <span class="billing-stat__label">Price</span>
                                    <span class="billing-stat__value">PKR {{ number_format($pricing['pkr'], 0) }}</span>
                                </div>
                                <div class="billing-stat">
                                    <span class="billing-stat__label">Status</span>
                                    <span class="billing-stat__value">
                                        @if ($tenant->isOnTrial())
                                            <span class="billing-status billing-status--trial">
                                                <i class="bi bi-hourglass-split"></i> Trial · {{ $tenant->trialDaysLeft() }} day(s) left
                                            </span>
                                        @elseif ($membership?->status === 'active')
                                            <span class="billing-status billing-status--active">
                                                <i class="bi bi-check-circle-fill"></i> Active
                                            </span>
                                        @else
                                            <span class="billing-status billing-status--due">
                                                <i class="bi bi-exclamation-circle-fill"></i> Payment required
                                            </span>
                                        @endif
                                    </span>
                                </div>
                                @if ($tenant->isOnTrial())
                                    <div class="billing-stat">
                                        <span class="billing-stat__label">Trial ends</span>
                                        <span class="billing-stat__value">{{ $tenant->trial_ends_at?->format('M d, Y') }}</span>
                                    </div>
                                @elseif ($membership?->status === 'active' && $membership->end_date)
                                    <div class="billing-stat">
                                        <span class="billing-stat__label">Renews / expires</span>
                                        <span class="billing-stat__value">{{ $membership->end_date->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        @if ($pendingBankTransfer)
                            <div class="billing-card mb-4">
                                <div class="billing-card__head billing-card__head--muted">
                                    <i class="bi bi-qr-code me-1"></i> Your bank transfer
                                </div>
                                <div class="billing-card__body">
                                    @include('front.pages.tenant.partials.bank-transfer-instructions', [
                                        'tenant' => $tenant,
                                        'payment' => $pendingBankTransfer,
                                        'invoice' => $pendingBankTransfer->invoice,
                                        'bank' => config('services.bank_transfer.pkr', []),
                                        'qrDataUri' => $bankTransferQr,
                                        'qrError' => $bankTransferQrError,
                                        'raastQrMode' => config('services.bank_transfer.raast_qr_mode', 'dynamic'),
                                        'showReportForm' => true,
                                    ])
                                    <div class="mt-3 pt-3 border-top">
                                        <a href="{{ route('tenant.billing.bank-transfer.show', $pendingBankTransfer) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Open full instructions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $canPayNow && ! $pendingBankTransfer && ! $pendingPayment)
                            <div class="billing-card h-100">
                                <div class="billing-card__body billing-empty d-flex flex-column align-items-center justify-content-center py-5">
                                    <i class="bi bi-shield-check d-block"></i>
                                    <p class="mb-0">You're all set — no payment is due right now.</p>
                                </div>
                            </div>
                        @elseif ($canPayNow)
                            <div class="billing-card">
                                <div class="billing-card__head">
                                    <i class="bi bi-credit-card me-1"></i>
                                    @if ($expiresSoon && ! $needsPayment)
                                        Renew your subscription
                                    @else
                                        Choose payment method
                                    @endif
                                </div>
                                <div class="billing-card__body">
                                    <div class="billing-price mb-2">
                                        PKR {{ number_format($pricing['pkr'], 0) }}
                                        <small>/ {{ $membership?->billing_cycle === 'yearly' ? 'year' : 'month' }}</small>
                                    </div>

                                    @if ($stripeReady)
                                        <div class="billing-pay-box mb-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-stripe fs-4 text-primary"></i>
                                                <strong>Pay with Stripe</strong>
                                                <span class="badge bg-success-subtle text-success-emphasis ms-auto">Instant</span>
                                            </div>
                                            <p class="text-muted small mb-3">
                                                Card payment — subscription activates automatically after checkout.
                                                @if ($pricing['usd'] > 0)
                                                    Approx. USD {{ number_format($pricing['usd'], 0) }} billed via Stripe.
                                                @endif
                                            </p>
                                            <form method="POST" action="{{ route('tenant.billing.stripe.checkout') }}">
                                                @csrf
                                                <button type="submit" class="btn btn-billing btn-lg w-100">
                                                    <i class="bi bi-lightning-charge me-1"></i> Pay now with Stripe
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    @if ($bankTransferReady)
                                        <div class="billing-pay-box mb-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-qr-code fs-4 text-primary"></i>
                                                <strong>Pay by bank transfer — scan QR</strong>
                                            </div>
                                            <p class="text-muted small mb-3">
                                                Pay from any Pakistani bank app (Raast or account transfer). We show a QR and unique payment ID — transfer, then submit your transaction ID.
                                            </p>
                                            @if ($pendingBankTransfer)
                                                <a href="{{ route('tenant.billing.bank-transfer.show', $pendingBankTransfer) }}"
                                                    class="btn btn-outline-primary btn-lg w-100">
                                                    <i class="bi bi-bank me-1"></i> View transfer instructions
                                                </a>
                                            @else
                                                <form method="POST" action="{{ route('tenant.billing.bank-transfer.checkout') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-lg w-100">
                                                        <i class="bi bi-qr-code me-1"></i> Pay now — get QR code
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($payfastReady)
                                        <div class="billing-pay-box mb-0">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-credit-card-2-front fs-4 text-primary"></i>
                                                <strong>Pay with PayFast</strong>
                                                <span class="badge bg-success-subtle text-success-emphasis ms-auto">Instant · PKR</span>
                                            </div>
                                            <p class="text-muted small mb-3">
                                                Cards, mobile wallets &amp; bank apps — activates automatically.
                                            </p>
                                            <form method="POST" action="{{ route('tenant.billing.payfast.checkout') }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-lg w-100">
                                                    Pay with PayFast (PKR)
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    @if (! $hasPaymentOptions)
                                        <div class="alert alert-warning mb-0">
                                            <strong>No payment method configured.</strong>
                                            <p class="small mb-0 mt-2">Add Stripe keys or Meezan bank details in platform settings, or contact support.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="billing-card mt-4">
                    <div class="billing-card__head billing-card__head--dark">Invoice history</div>
                    <div class="billing-card__body p-0">
                        @include('front.pages.tenant.partials.invoice-table', ['invoices' => $invoices])
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection
