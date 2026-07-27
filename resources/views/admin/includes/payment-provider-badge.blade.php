@php
    $provider = $payment->resolvedProvider();
    $providerClass = match ($provider) {
        'stripe' => 'crm-provider-badge--stripe',
        'paypal' => 'crm-provider-badge--paypal',
        default => 'crm-provider-badge--unknown',
    };
    $providerLabel = match ($provider) {
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        default => ucfirst($provider ?: 'Unknown'),
    };
@endphp
<span class="crm-provider-badge {{ $providerClass }}">{{ $providerLabel }}</span>
