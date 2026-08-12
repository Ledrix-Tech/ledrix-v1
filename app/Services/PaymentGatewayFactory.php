<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\AccountKey;
use App\Services\PayPalGateway;
use App\Services\StripeGateway;
use Illuminate\Support\Facades\Log;

class PaymentGatewayFactory
{
    public function forProviderWithBrand(string $provider, Brand $brand): PaymentGateway
    {
        $keys = $this->activeKeysForBrand($brand, 'ppc');

        if (! $keys || ! $this->hasProviderKeys($keys, $provider)) {
            throw new \RuntimeException(
                "No valid {$provider} PPC payment keys configured for brand #{$brand->id}."
            );
        }

        return $this->buildGateway($provider, $keys);
    }

    /** Whether the brand has an active merchant AccountKey with usable secrets for the provider. */
    public function brandHasProvider(Brand $brand, string $provider, string $module = 'ppc'): bool
    {
        $keys = $this->activeKeysForBrand($brand, $module);

        return $keys !== null && $this->hasProviderKeys($keys, $provider);
    }

    /**
     * @return list<string>
     */
    public function availableProvidersForBrand(Brand $brand, string $module = 'ppc'): array
    {
        $keys = $this->activeKeysForBrand($brand, $module);
        if (! $keys) {
            return [];
        }

        $out = [];
        foreach (['stripe', 'paypal'] as $provider) {
            if ($this->hasProviderKeys($keys, $provider)) {
                $out[] = $provider;
            }
        }

        return $out;
    }

    public function activeKeysForBrand(Brand $brand, string $module = 'ppc'): ?AccountKey
    {
        return AccountKey::withoutGlobalScopes()
            ->where('brand_id', $brand->id)
            ->where('module', $module)
            ->where('status', 'active')
            ->first();
    }

    public function forProvider(string $provider): PaymentGateway
    {
        return match ($provider) {
            'stripe' => new StripeGateway(config('services.stripe.secret')),
            'paypal' => new PayPalGateway([
                'client_id'  => config('services.paypal.client_id'),
                'secret'     => config('services.paypal.secret'),
                'base'       => config('services.paypal.base', 'https://api-m.sandbox.paypal.com'),
                'webhook_id' => config('services.paypal.webhook_id'),
            ]),
            default => throw new \InvalidArgumentException("Unsupported provider [{$provider}]"),
        };
    }

    protected function buildGateway(string $provider, AccountKey $keys): PaymentGateway
    {
        return match ($provider) {
            'stripe' => new StripeGateway($keys->stripe_secret_key),
            'paypal' => new PayPalGateway([
                'client_id'  => $keys->paypal_client_id,
                'secret'     => $keys->paypal_secret,
                'base'       => $keys->paypal_base_url ?? config('services.paypal.base', 'https://api-m.sandbox.paypal.com'),
                'webhook_id' => $keys->paypal_webhook_id,
            ]),
            default => throw new \InvalidArgumentException("Unsupported provider [{$provider}]"),
        };
    }

    public function hasProviderKeys(AccountKey $keys, string $provider): bool
    {
        return match ($provider) {
            'stripe' => ! empty($keys->stripe_secret_key),
            'paypal' => ! empty($keys->paypal_client_id) && ! empty($keys->paypal_secret),
            default  => false,
        };
    }
}
