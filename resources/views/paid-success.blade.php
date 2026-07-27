@extends('payments.layout')

@section('title', 'Payment Successful')

@section('content')
    @php
        $brandUrl = trim((string) ($brand->brand_url ?? ($order?->brand?->brand_url ?? '')));
        $brandUrl = $brandUrl ? (str_starts_with($brandUrl, 'http') ? $brandUrl : 'https://' . $brandUrl) : null;
        $provider = $link->provider ?? 'stripe';
    @endphp

    <div class="pay-card-header">
        <div class="pay-icon pay-icon--success">✓</div>
        <h1>Payment successful</h1>
        <p>Thank you! Your payment has been processed securely.</p>
    </div>

    <div class="pay-card-body">
        <div class="pay-summary">
            <div class="pay-summary-row">
                <span>Service</span>
                <span>{{ $link->service_name }}</span>
            </div>
            <div class="pay-summary-row">
                <span>Amount paid</span>
                <span class="pay-amount-highlight">
                    {{ number_format($link->unit_amount / 100, 2) }}
                    {{ strtoupper($link->currency) }}
                </span>
            </div>
            <div class="pay-summary-row">
                <span>Paid at</span>
                <span>{{ $link->paid_at?->format('M j, Y g:i A') ?? now()->format('M j, Y g:i A') }}</span>
            </div>
            @if ($link->provider_payment_intent_id)
                <div class="pay-summary-row">
                    <span>Transaction ID</span>
                    <span style="font-size:0.75rem; word-break:break-all;">{{ $link->provider_payment_intent_id }}</span>
                </div>
            @endif
            <div class="pay-summary-row">
                <span>Provider</span>
                <span>{{ ucfirst($provider) }}</span>
            </div>
        </div>

        <div class="pay-actions">
            @if ($brandUrl)
                <a href="{{ $brandUrl }}" class="pay-btn pay-btn--primary">Return to website</a>
            @endif
        </div>
    </div>
@endsection
