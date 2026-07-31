<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Questionnair;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

/**
 * Seller portal authorization tests (Phase 2 security).
 *
 * @group seller
 * @group security
 */
class SellerPortalSecurityTest extends TestCase
{
    use CreatesPaymentFlow;
    use CreatesPortalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    private function createLogoOrderForClient(Client $client, array $graphOverrides = []): Order
    {
        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph(array_merge([
            'lead' => ['client_id' => $client->id],
        ], $graphOverrides));

        return Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Logo Design',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 5_000,
            'status'          => 'paid',
            'order_type'      => 'original',
        ]);
    }

    public function test_seller_cannot_view_lead_from_another_brand(): void
    {
        $sellerA = $this->createSellerUser();
        $sellerB = $this->createSellerUser();

        ['lead' => $leadB] = $this->createPaymentLeadGraph([
            'lead' => ['brand_id' => $sellerB->brand_id, 'seller_id' => $sellerB->id],
        ]);

        $this->actingAs($sellerA, 'seller')
            ->get(route('seller.lead-details.get', ['id' => $leadB->id]))
            ->assertForbidden();
    }

    public function test_seller_cannot_access_foreign_client_briefs(): void
    {
        $sellerA = $this->createSellerUser();
        $sellerB = $this->createSellerUser();
        $client = Client::factory()->create(['tenant_id' => 1]);

        $this->createLogoOrderForClient($client, [
            'lead' => [
                'brand_id'  => $sellerB->brand_id,
                'seller_id' => $sellerB->id,
                'client_id' => $client->id,
            ],
        ]);

        $this->actingAs($sellerA, 'seller')
            ->get(route('seller.client-briefs.get', ['id' => $client->id]))
            ->assertForbidden();
    }

    public function test_seller_cannot_update_brief_status_on_foreign_order(): void
    {
        $sellerA = $this->createSellerUser();
        $sellerB = $this->createSellerUser();
        $client = Client::factory()->create(['tenant_id' => 1]);

        $order = $this->createLogoOrderForClient($client, [
            'lead' => [
                'brand_id'  => $sellerB->brand_id,
                'seller_id' => $sellerB->id,
                'client_id' => $client->id,
            ],
        ]);

        Questionnair::create([
            'tenant_id'    => 1,
            'client_id'    => $client->id,
            'order_id'     => $order->id,
            'service_name' => 'Logo Design',
            'meta'         => ['query' => ['company_name' => 'Acme']],
            'status'       => 'progress',
        ]);

        $this->actingAs($sellerA, 'seller')
            ->post(route('seller.brief-status.post'), [
                'order_id' => $order->id,
                'status'   => 'completed',
            ])
            ->assertForbidden();
    }

    public function test_seller_can_update_brief_status_on_own_order(): void
    {
        $client = Client::factory()->create(['tenant_id' => 1]);
        $order = $this->createLogoOrderForClient($client);
        $seller = $order->seller;

        Questionnair::create([
            'tenant_id'    => 1,
            'client_id'    => $client->id,
            'order_id'     => $order->id,
            'service_name' => 'Logo Design',
            'meta'         => ['query' => ['company_name' => 'Acme']],
            'status'       => 'progress',
        ]);

        $this->actingAs($seller, 'seller')
            ->post(route('seller.brief-status.post'), [
                'order_id' => $order->id,
                'status'   => 'completed',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('completed', Questionnair::where('order_id', $order->id)->value('status'));
    }
}
