<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;

/**
 * Resolves whether a tenant bills as Pakistan (PKR) or international (USD).
 *
 * Detection order:
 * 1. preferred_billing_currency if set (PKR|USD)
 * 2. country from registration (PK → PKR, else USD)
 * 3. default USD
 */
class TenantBillingRegion
{
    public const CURRENCY_PKR = 'PKR';

    public const CURRENCY_USD = 'USD';

    public static function isPakistanCountry(?string $country): bool
    {
        $code = strtoupper(trim((string) $country));

        return in_array($code, ['PK', 'PAK', 'PAKISTAN'], true);
    }

    public static function currencyFromCountry(?string $country): string
    {
        return self::isPakistanCountry($country)
            ? self::CURRENCY_PKR
            : self::CURRENCY_USD;
    }

    public static function currencyForTenant(Tenant $tenant): string
    {
        $preferred = strtoupper(trim((string) ($tenant->preferred_billing_currency ?? '')));

        if (in_array($preferred, [self::CURRENCY_PKR, self::CURRENCY_USD], true)) {
            return $preferred;
        }

        return self::currencyFromCountry($tenant->country);
    }

    public static function isPakistanBuyer(Tenant $tenant): bool
    {
        return self::currencyForTenant($tenant) === self::CURRENCY_PKR;
    }

    public static function regionLabel(Tenant $tenant): string
    {
        return self::isPakistanBuyer($tenant) ? 'Pakistan (PKR)' : 'International (USD)';
    }

    /**
     * Persist currency from country when unset (idempotent).
     */
    public static function syncPreferredCurrency(Tenant $tenant): string
    {
        $currency = self::currencyForTenant($tenant);

        if (strtoupper((string) $tenant->preferred_billing_currency) !== $currency) {
            $tenant->forceFill(['preferred_billing_currency' => $currency])->save();
        }

        return $currency;
    }
}
