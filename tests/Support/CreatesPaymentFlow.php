<?php

namespace Tests\Support;

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

        $seller = Seller::factory()->create([
            'brand_id'  => $brand->id,
            'is_seller' => 'front_seller',
        ]);

        $client = Client::factory()->create();

        $lead = Lead::factory()->create(array_merge([
            'brand_id'  => $brand->id,
            'seller_id' => $seller->id,
            'client_id' => $client->id,
            'status'    => 'qualified',
            'service'   => 'Web Design',
        ], $overrides['lead'] ?? []));

        return compact('brand', 'seller', 'client', 'lead');
    }
}
