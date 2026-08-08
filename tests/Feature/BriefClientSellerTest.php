<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Questionnair;
use App\Services\Tenant\TenantFeatureService;
use App\Support\BriefServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

/**
 * Client brief submissions must appear on the seller order brief view.
 *
 * @group client
 * @group brief
 */
class BriefClientSellerTest extends TestCase
{
    use CreatesPaymentFlow;
    use CreatesPortalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
            $mock->shouldReceive('assertAnyEnabled')->andReturnNull();
        });

        $this->mockCrmWorkspaceAccess();
    }

    private function createPortalClient(array $attributes = []): Client
    {
        return Client::factory()->create(array_merge([
            'tenant_id' => 1,
            'status'    => 'Active',
            'password'  => 'password123',
            'meta'      => ['portal_access' => true],
        ], $attributes));
    }

    private function createLogoOrder(Client $client, array $overrides = []): Order
    {
        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $client->id],
        ]);

        return Order::create(array_merge([
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
        ], $overrides));
    }

    public function test_client_brief_submission_is_visible_to_seller_on_same_order(): void
    {
        $client = $this->createPortalClient();
        $order = $this->createLogoOrder($client);
        $seller = $order->seller;

        $this->actingAs($client, 'client')
            ->post(route('client.brief-form.post'), [
                'order_id' => $order->id,
                'query'    => [
                    'company_name' => 'Acme Widgets LLC',
                    'industry'     => 'Technology',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $brief = Questionnair::where('order_id', $order->id)->first();
        $this->assertNotNull($brief);
        $this->assertSame('progress', $brief->status);
        $this->assertSame('Acme Widgets LLC', $brief->meta['query']['company_name']);

        $this->mockTenantFeaturesEnabled();

        $this->actingAs($seller, 'seller')
            ->get(route('seller.client-briefs.get', ['id' => $client->id, 'order' => $order->id]))
            ->assertOk()
            ->assertSee('Acme Widgets LLC', false)
            ->assertSee('Update status', false)
            ->assertSee('Submitted project info', false)
            ->assertDontSee('name="query[company_name]"', false);

        $this->actingAs($seller, 'seller')
            ->post(route('seller.brief-status.post'), [
                'order_id' => $order->id,
                'status'   => 'completed',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('completed', $brief->fresh()->status);
    }

    public function test_seller_cannot_submit_brief_content_on_behalf_of_client(): void
    {
        $client = $this->createPortalClient();
        $order = $this->createLogoOrder($client);
        $seller = $order->seller;

        $this->mockTenantFeaturesEnabled();

        $this->actingAs($seller, 'seller')
            ->post('/seller/brief-form', [
                'order_id' => $order->id,
                'query'    => ['company_name' => 'Seller Filled'],
            ])
            ->assertNotFound();
    }

    public function test_filter_orders_for_briefs_keeps_one_tab_per_order_not_per_service(): void
    {
        $client = $this->createPortalClient();
        $orderA = $this->createLogoOrder($client);
        $orderB = $this->createLogoOrder($client);

        $filtered = BriefServiceCatalog::filterOrdersForBriefs(collect([$orderA, $orderB]));

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->pluck('id')->contains($orderA->id));
        $this->assertTrue($filtered->pluck('id')->contains($orderB->id));
    }

    public function test_filter_orders_for_briefs_accepts_paginator(): void
    {
        $client = $this->createPortalClient();
        $order = $this->createLogoOrder($client);

        $paginator = Order::query()
            ->where('client_id', $client->id)
            ->paginate(20);

        $filtered = BriefServiceCatalog::filterOrdersForBriefs($paginator);

        $this->assertCount(1, $filtered);
        $this->assertSame($order->id, $filtered->first()->id);
    }

    public function test_unknown_service_has_no_questionnaire(): void
    {
        $this->assertFalse(BriefServiceCatalog::hasQuestionnaire('Custom Mystery Service'));
        $this->assertNull(BriefServiceCatalog::viewKeyFor('Custom Mystery Service'));
        $this->assertTrue(BriefServiceCatalog::hasQuestionnaire('Logo Design'));
    }

    public function test_completed_brief_cannot_be_edited_by_client(): void
    {
        $client = $this->createPortalClient();
        $order = $this->createLogoOrder($client);

        Questionnair::create([
            'tenant_id'    => 1,
            'order_id'     => $order->id,
            'client_id'    => $client->id,
            'service_name' => 'Logo Design',
            'status'       => 'completed',
            'meta'         => ['query' => ['company_name' => 'Locked Co']],
        ]);

        $this->actingAs($client, 'client')
            ->post(route('client.brief-form.post'), [
                'order_id' => $order->id,
                'query'    => ['company_name' => 'Should Fail'],
            ])
            ->assertSessionHasErrors('order_id');

        $this->assertSame('Locked Co', Questionnair::where('order_id', $order->id)->first()->meta['query']['company_name']);
    }
}
