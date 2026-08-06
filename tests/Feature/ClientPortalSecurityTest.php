<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientTicket;
use App\Models\Order;
use App\Models\Questionnair;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\TestCase;

/**
 * Client portal authorization tests (Phase 1 security).
 *
 * @group client
 * @group security
 */
class ClientPortalSecurityTest extends TestCase
{
    use CreatesPaymentFlow;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
            $mock->shouldReceive('assertAnyEnabled')->andReturnNull();
        });

        $this->mock(SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(true);
        });
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

    public function test_login_rejects_clients_without_portal_access(): void
    {
        $this->createPortalClient([
            'email' => 'locked@example.com',
            'meta'  => ['portal_access' => false],
        ]);

        $this->post(route('client.login.post'), [
            'email'    => 'locked@example.com',
            'password' => 'password123',
        ])->assertSessionHas('error');

        $this->assertGuest('client');
    }

    public function test_client_cannot_view_another_clients_invoice(): void
    {
        $owner = $this->createPortalClient();
        $other = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $other->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $other->id,
            'service_name'    => 'Other Client Order',
            'currency'        => 'USD',
            'unit_amount'     => 10_000,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'order_type'      => 'original',
        ]);

        $this->actingAs($owner, 'client')
            ->get(route('client.invoice.details', $order))
            ->assertForbidden();
    }

    public function test_client_can_view_own_invoice(): void
    {
        $client = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $client->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Own Order',
            'currency'        => 'USD',
            'unit_amount'     => 10_000,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'order_type'      => 'original',
        ]);

        $this->actingAs($client, 'client')
            ->get(route('client.invoice.details', $order))
            ->assertOk();
    }

    public function test_client_tickets_list_is_scoped_to_authenticated_client(): void
    {
        $client = $this->createPortalClient();
        $other = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $client->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Ticket Order',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'order_type'      => 'original',
        ]);

        ['brand' => $otherBrand, 'seller' => $otherSeller, 'lead' => $otherLead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $other->id],
        ]);

        $otherOrder = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $otherLead->id,
            'brand_id'        => $otherBrand->id,
            'seller_id'       => $otherSeller->id,
            'front_seller_id' => $otherSeller->id,
            'owner_seller_id' => $otherSeller->id,
            'client_id'       => $other->id,
            'service_name'    => 'Other Ticket Order',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'order_type'      => 'original',
        ]);

        ClientTicket::create([
            'tenant_id'   => 1,
            'client_id'   => $client->id,
            'brand_id'    => $brand->id,
            'seller_id'   => $seller->id,
            'order_id'    => $order->id,
            'subject'     => 'Mine',
            'description' => 'Visible',
            'priority'    => 'low',
            'status'      => 'open',
            'source'      => 'crm',
        ]);

        ClientTicket::create([
            'tenant_id'   => 1,
            'client_id'   => $other->id,
            'brand_id'    => $otherBrand->id,
            'seller_id'   => $otherSeller->id,
            'order_id'    => $otherOrder->id,
            'subject'     => 'Theirs',
            'description' => 'Hidden',
            'priority'    => 'low',
            'status'      => 'open',
            'source'      => 'crm',
        ]);

        $response = $this->actingAs($client, 'client')
            ->get(route('client.raised-tickets.get'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }

    public function test_client_cannot_raise_ticket_on_another_clients_order(): void
    {
        $owner = $this->createPortalClient();
        $other = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $other->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $other->id,
            'service_name'    => 'Other Order',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'order_type'      => 'original',
        ]);

        $this->actingAs($owner, 'client')
            ->post(route('client.raised-tickets.post'), [
                'order_id'    => $order->id,
                'subject'     => 'IDOR attempt',
                'description' => 'Should fail',
                'priority'    => 'low',
            ])
            ->assertNotFound();
    }

    public function test_forgot_password_rejects_clients_without_portal_access(): void
    {
        $this->createPortalClient([
            'email' => 'locked@example.com',
            'meta'  => ['portal_access' => false],
        ]);

        $this->post(route('client.forgot.post'), [
            'email' => 'locked@example.com',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'locked@example.com',
        ]);
    }

    public function test_logged_in_client_cannot_submit_brief_for_another_client(): void
    {
        $owner = $this->createPortalClient();
        $other = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $other->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $other->id,
            'service_name'    => 'Logo Design',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 5_000,
            'status'          => 'paid',
            'order_type'      => 'original',
        ]);

        $brief = Questionnair::create([
            'tenant_id'              => 1,
            'client_id'              => $other->id,
            'order_id'               => $order->id,
            'service_name'           => 'Logo Design',
            'brief_token'            => (string) \Illuminate\Support\Str::uuid(),
            'brief_token_expires_at' => now()->addDays(7),
            'status'                 => 'pending',
        ]);

        $this->actingAs($owner, 'client')
            ->post(route('brief.submit', ['token' => $brief->brief_token]), [
                'query' => ['company_name' => 'Hijack'],
            ])
            ->assertForbidden();
    }
}
