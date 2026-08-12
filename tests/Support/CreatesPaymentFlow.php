<?php

namespace Tests\Support;

use App\Models\AccountKey;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Seller;

trait CreatesPaymentFlow
{
    /**
     * @return array{brand: Brand, seller: Seller, client: Client, lead: Lead}
     */
    protected function createPaymentLeadGraph(array $overrides = []): array
    {
        $brand = Brand::factory()->create([
            'status' => 'Active',
        ]);

        AccountKey::query()->create(array_merge([
            'tenant_id'          => $brand->tenant_id ?? 1,
            'brand_id'           => $brand->id,
            'module'             => 'ppc',
            'status'             => 'active',
            'stripe_secret_key'  => 'sk_test_payment_flow',
            'paypal_client_id'   => 'paypal_client_test',
            'paypal_secret'      => 'paypal_secret_test',
        ], $overrides['account_key'] ?? []));

        $seller = Seller::factory()->create([
            'brand_id'  => $brand->id,
            'is_seller' => 'front_seller',
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $brand->tenant_id ?? 1,
        ]);

        $lead = Lead::factory()->create(array_merge([
            'tenant_id' => $brand->tenant_id ?? 1,
            'brand_id'  => $brand->id,
            'seller_id' => $seller->id,
            'client_id' => $client->id,
            'status'    => 'qualified',
            'service'   => 'Web Design',
        ], $overrides['lead'] ?? []));

        return compact('brand', 'seller', 'client', 'lead');
    }
}
