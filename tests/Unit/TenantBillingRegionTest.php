<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Services\Billing\TenantBillingRegion;
use PHPUnit\Framework\TestCase;

class TenantBillingRegionTest extends TestCase
{
    public function test_pakistan_country_codes_map_to_pkr(): void
    {
        $this->assertTrue(TenantBillingRegion::isPakistanCountry('PK'));
        $this->assertTrue(TenantBillingRegion::isPakistanCountry('pakistan'));
        $this->assertSame('PKR', TenantBillingRegion::currencyFromCountry('PK'));
        $this->assertSame('USD', TenantBillingRegion::currencyFromCountry('AE'));
        $this->assertSame('USD', TenantBillingRegion::currencyFromCountry('US'));
    }

    public function test_preferred_currency_overrides_country(): void
    {
        $tenant = new Tenant([
            'country' => 'PK',
            'preferred_billing_currency' => 'USD',
        ]);

        $this->assertSame('USD', TenantBillingRegion::currencyForTenant($tenant));
        $this->assertFalse(TenantBillingRegion::isPakistanBuyer($tenant));
    }

    public function test_country_used_when_preferred_missing(): void
    {
        $tenant = new Tenant([
            'country' => 'PK',
            'preferred_billing_currency' => null,
        ]);

        $this->assertSame('PKR', TenantBillingRegion::currencyForTenant($tenant));
        $this->assertTrue(TenantBillingRegion::isPakistanBuyer($tenant));
    }
}
