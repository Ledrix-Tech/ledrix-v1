<?php

// app/Services/Payments/PaymentGatewayFactory.php
namespace App\Services\Upwork;

use App\Models\Brand;
use App\Models\AccountKey;
use Illuminate\Support\Facades\Log;
use App\Services\Upwork\UpworkStripePayment;
use App\Services\Upwork\UpworkPaymentGateway;

class UpworkPaymentGatewayFactory
{
    /**
     * Get the appropriate payment gateway (Stripe or PayPal) based on provider and brand.
     */
    public function forProviderWithBrand(string $provider, Brand $brand, string $module): UpworkPaymentGateway
    {
        $provider = strtolower($provider);
        $module   = strtolower($module); // 'upwork' or 'ppc'

        // Validate that module is either 'upwork' or 'ppc'
        if (!in_array($module, ['upwork', 'ppc'], true)) {
            throw new \InvalidArgumentException("Invalid module [$module]");
        }

        // 1) Try to get brand + module specific keys
        $keys = AccountKey::where('brand_id', $brand->id)
            ->where('module', $module)
            ->where('status', 'active')
            ->first();

        // 2) Fallback to super account if no brand/module-specific keys
        if (!$keys || !$this->hasProviderKeys($keys, $provider)) {
            Log::warning("{$module}: Using account keys for brand {$brand->id} (provider: {$provider})");

            $keys = AccountKey::where('module', $module)
                ->where('status', 'active')
                ->first();

            // 3) Fallback to environment variables if no keys found in DB
            if (!$keys || !$this->hasProviderKeys($keys, $provider)) {
                Log::warning("{$module}: No DB keys found; falling back to ENV (provider: {$provider})");
                return $this->forProvider($provider); // Fallback to ENV
            }
        }

        // Create the gateway based on the provider
        return $this->buildGateway($provider, $keys);
    }

    /**
     * Return the appropriate gateway for the given provider (Stripe or PayPal).
     */
    public function forProvider(string $provider): UpworkPaymentGateway
    {
        $provider = strtolower($provider);

        return match ($provider) {
            'stripe' => $this->stripeFromEnv(),  // Stripe with ENV keys
            'paypal' => $this->paypalFromEnv(),  // PayPal with ENV keys
            default  => throw new \Exception("Unsupported provider [$provider]"),
        };
    }

    /**
     * Build and return the payment gateway using the keys from the DB.
     */
    protected function buildGateway(string $provider, AccountKey $keys): UpworkPaymentGateway
    {
        return match ($provider) {
            'stripe' => app()->make(\App\Services\Upwork\UpworkStripePayment::class, [
                'secret' => $keys->stripe_secret_key,
            ]),
            'paypal' => new UpworkPayPalPayment([
                'client_id'  => $keys->paypal_client_id ?? '',
                'secret'     => $keys->paypal_secret ?? '',
                'base'       => $keys->paypal_base_url ?? 'https://api.paypal.com',
                'webhook_id' => $keys->paypal_webhook_id ?? null
            ]),
            default => throw new \Exception("Unsupported provider [$provider]"),
        };
    }

    // protected function buildGateway(string $provider, AccountKey $keys): UpworkPaymentGateway
    // {
    //     return match ($provider) {
    //         'stripe' => new UpworkStripePayment($keys->stripe_secret_key),
    //         'paypal' => new UpworkPayPalPayment([
    //             'client_id'  => $keys->paypal_client_id ?? '',
    //             'secret'     => $keys->paypal_secret ?? '',
    //             'base'       => $keys->paypal_base_url ?? 'https://api.paypal.com',
    //             'webhook_id' => $keys->paypal_webhook_id ?? null
    //         ]),
    //         default  => throw new \Exception("Unsupported provider [$provider]"),
    //     };
    // }

    /**
     * Check if the provider-specific keys exist in the provided account keys.
     */
    protected function hasProviderKeys(AccountKey $keys, string $provider): bool
    {
        return match ($provider) {
            'stripe' => !empty($keys->stripe_secret_key),
            'paypal' => !empty($keys->paypal_client_id) && !empty($keys->paypal_secret),
            default  => false,
        };
    }

    /**
     * Fallback method to get Stripe gateway using ENV variables.
     */
    protected function stripeFromEnv(): UpworkPaymentGateway
    {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            throw new \Exception("Stripe ENV secret is missing (services.stripe.secret).");
        }

        return app()->make(\App\Services\Upwork\UpworkStripePayment::class, [
            'secret' => $secret,
        ]);
    }


    /**
     * Fallback method to get PayPal gateway using ENV variables.
     */
    protected function paypalFromEnv(): UpworkPaymentGateway
    {
        $clientId = config('services.paypal.client_id');
        $secret   = config('services.paypal.secret');

        if (empty($clientId) || empty($secret)) {
            throw new \Exception("PayPal ENV keys missing (services.paypal.client_id / services.paypal.secret).");
        }

        return new UpworkPayPalPayment([
            'client_id'  => $clientId,
            'secret'     => $secret,
            'base'       => config('services.paypal.base_url', 'https://api.paypal.com'),
            'webhook_id' => config('services.paypal.webhook_id'),
        ]);
    }
}
