
@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
<div class="tenant-billing-page">
        @unless ($isAdminOrg)
        <header class="hero d-flex align-items-center justify-content-center text-center"
            style="background: linear-gradient(rgba(30,27,75,.72), rgba(68,56,201,.65)), url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center; background-size: cover;">
            <div class="container text-white">
                <h1>Billing & Subscription</h1>
                <p class="mb-0">{{ $tenant->name }}</p>
            </div>
        </header>
        @endunless

        <main class="{{ $isAdminOrg ? 'pb-4' : 'py-5' }}">
            <div class="{{ $isAdminOrg ? '' : 'container' }}">
                @if ($isAdminOrg)
                    <div class="crm-page-header mb-4">
                        <div>
                            <h1>Billing & Subscription</h1>
                            <p>Manage plan, invoices, and payments for {{ $tenant->name }}.</p>
                        </div>
                    </div>
                @else
                <div class="mb-4">
                    <a href="{{ org_route('dashboard') }}" class="text-muted small text-decoration-none">&larr; Back to dashboard</a>
                    <h4 class="mb-0 mt-1">Manage your plan</h4>
                </div>
                @endif

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
                        <a href="{{ org_route('billing') }}" class="alert-link">Renew early</a>
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
                                    <span class="billing-stat__value">
                                        {{ $billingCurrency }} {{ number_format($displayAmount, $billingCurrency === 'USD' ? 2 : 0) }}
                                    </span>
                                </div>
                                @if ($billingCredit > 0)
                                    <div class="billing-stat">
                                        <span class="billing-stat__label">Account credit</span>
                                        <span class="billing-stat__value text-success">
                                            {{ $billingCurrency }} {{ number_format($billingCredit, $billingCurrency === 'USD' ? 2 : 0) }}
                                        </span>
                                    </div>
                                @endif
                                @if ($pendingReferralDiscount)
                                    <div class="billing-stat">
                                        <span class="billing-stat__label">Referral discount</span>
                                        <span class="billing-stat__value">
                                            @if (($pendingReferralDiscount['type'] ?? '') === 'percent')
                                                {{ rtrim(rtrim(number_format((float) $pendingReferralDiscount['value'], 2), '0'), '.') }}% off next invoice
                                            @else
                                                {{ strtoupper($pendingReferralDiscount['currency'] ?? $billingCurrency) }}
                                                {{ number_format((float) ($pendingReferralDiscount['value'] ?? 0), 2) }} off next invoice
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                <div class="billing-stat">
                                    <span class="billing-stat__label">Billing region</span>
                                    <span class="billing-stat__value">{{ $billingRegionLabel }}</span>
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
                                        <a href="{{ org_route('billing.bank-transfer.show', $pendingBankTransfer) }}"
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
                                        {{ $billingCurrency }} {{ number_format($displayAmount, $billingCurrency === 'USD' ? 2 : 0) }}
                                        <small>/ {{ $membership?->billing_cycle === 'yearly' ? 'year' : 'month' }}</small>
                                    </div>

                                    <div class="billing-pay-box mb-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <strong>Paying as {{ $billingRegionLabel }}</strong>
                                        </div>
                                        <p class="text-muted small mb-2">
                                            Detected from your registration country
                                            ({{ $tenant->country ?: 'not set' }}).
                                            Pakistan buyers pay in PKR; international buyers pay in USD via Stripe.
                                        </p>
                                        <form method="POST" action="{{ org_route('billing.currency') }}" class="d-flex flex-wrap gap-2">
                                            @csrf
                                            @if ($isPakistanBuyer)
                                                <input type="hidden" name="preferred_billing_currency" value="USD">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    Switch to International (USD / Stripe)
                                                </button>
                                            @else
                                                <input type="hidden" name="preferred_billing_currency" value="PKR">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    Switch to Pakistan (PKR / Meezan)
                                                </button>
                                            @endif
                                        </form>
                                    </div>

                                    @if ($stripeReady)
                                        <div class="billing-pay-box mb-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-stripe fs-4 text-primary"></i>
                                                <strong>Pay with Stripe</strong>
                                                <span class="badge bg-success-subtle text-success-emphasis ms-auto">Instant · USD</span>
                                            </div>
                                            <p class="text-muted small mb-3">
                                                International card payment (Stripe UAE) —
                                                billed in USD {{ number_format($pricing['usd'], 2) }}. Activates automatically.
                                            </p>
                                            <form method="POST" action="{{ org_route('billing.stripe.checkout') }}">
                                                @csrf
                                                <button type="submit" class="btn btn-billing btn-lg w-100">
                                                    <i class="bi bi-lightning-charge me-1"></i> Pay {{ number_format($pricing['usd'], 2) }} USD with Stripe
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    @if ($bankTransferReady)
                                        <div class="billing-pay-box mb-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-qr-code fs-4 text-primary"></i>
                                                <strong>Pay by Meezan bank transfer</strong>
                                                <span class="badge bg-primary-subtle text-primary-emphasis ms-auto">PKR</span>
                                            </div>
                                            <p class="text-muted small mb-3">
                                                Pay PKR {{ number_format($pricing['pkr'], 0) }} from any Pakistani bank app (Raast).
                                                Transfer, then submit your transaction ID for confirmation.
                                            </p>
                                            @if ($pendingBankTransfer)
                                                <a href="{{ org_route('billing.bank-transfer.show', $pendingBankTransfer) }}"
                                                    class="btn btn-outline-primary btn-lg w-100">
                                                    <i class="bi bi-bank me-1"></i> View transfer instructions
                                                </a>
                                            @else
                                                <form method="POST" action="{{ org_route('billing.bank-transfer.checkout') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-lg w-100">
                                                        <i class="bi bi-qr-code me-1"></i> Pay PKR {{ number_format($pricing['pkr'], 0) }} — get QR
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
                                                Pakistan cards, wallets &amp; bank apps — PKR {{ number_format($pricing['pkr'], 0) }}. Activates automatically.
                                            </p>
                                            <form method="POST" action="{{ org_route('billing.payfast.checkout') }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-lg w-100">
                                                    Pay with PayFast (PKR)
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    @if (! $hasPaymentOptions)
                                        <div class="alert alert-warning mb-0">
                                            <strong>No payment method available for {{ $billingRegionLabel }}.</strong>
                                            <p class="small mb-0 mt-2">
                                                @if ($isPakistanBuyer)
                                                    Enable Meezan (or PayFast) in Super Admin → Payment Accounts, or switch to International USD.
                                                @else
                                                    Enable Stripe in Super Admin → Payment Accounts, or switch to Pakistan PKR.
                                                @endif
                                            </p>
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
