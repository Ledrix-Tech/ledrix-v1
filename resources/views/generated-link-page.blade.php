@extends('payments.layout')

@section('title', 'Complete Payment')

@section('content')
    @php
        $client = $client ?? $lead;
        $clientName = $client->name ?? ($lead->name ?? 'Customer');
        $nameParts  = explode(' ', $clientName, 2);
        $fName      = $nameParts[0] ?? '';
        $lName      = $nameParts[1] ?? '';
        $provider   = $link->provider ?? 'stripe';
    @endphp

    <div class="pay-card-header">
        <div class="pay-icon pay-icon--info">💳</div>
        <h1>Complete your payment</h1>
        <p>Review your order details and proceed to secure checkout.</p>
    </div>

    <div class="pay-card-body">
        <div class="pay-summary">
            <div class="pay-summary-row">
                <span>Service</span>
                <span>{{ $service ?? $link->service_name }}</span>
            </div>
            <div class="pay-summary-row">
                <span>Amount due</span>
                <span class="pay-amount-highlight">
                    {{ number_format(($amount ?? $link->unit_amount) / 100, 2) }}
                    {{ strtoupper($currency ?? $link->currency) }}
                </span>
            </div>
            @if ($link->expires_at)
                <div class="pay-summary-row">
                    <span>Link expires</span>
                    <span>{{ $link->expires_at->format('M j, Y g:i A') }}</span>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('paylinks.checkout', $token) }}">
            @csrf

            <div class="pay-grid">
                <div class="pay-field">
                    <label>First name</label>
                    <input type="text" name="first_name" value="{{ $fName }}" readonly>
                </div>
                <div class="pay-field">
                    <label>Last name</label>
                    <input type="text" name="last_name" value="{{ $lName }}" readonly>
                </div>
            </div>

            <div class="pay-grid">
                <div class="pay-field">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ $client->email ?? $lead->email ?? '' }}" readonly required>
                </div>
                <div class="pay-field">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ $client->phone ?? $lead->phone ?? '' }}" readonly>
                </div>
            </div>

            <button type="submit" class="pay-btn {{ $provider === 'paypal' ? 'pay-btn--paypal' : 'pay-btn--stripe' }}">
                @if ($provider === 'paypal')
                    Continue with PayPal
                @else
                    Continue with Stripe
                @endif
            </button>

            <div class="pay-secure">
                🔒 SSL encrypted · PCI compliant checkout
            </div>
        </form>
    </div>
@endsection
