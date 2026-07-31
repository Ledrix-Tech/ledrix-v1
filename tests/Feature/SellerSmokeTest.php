<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\TestCase;

/**
 * Smoke tests: seller portal routes render without 5xx for authenticated sellers.
 *
 * @group smoke
 * @group seller
 */
class SellerSmokeTest extends TestCase
{
    use CreatesPaymentFlow;
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->migrateUpworkTables();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_seller_login_page_loads(): void
    {
        $this->get(route('seller.login.get'))
            ->assertOk();
    }

    public function test_guest_is_redirected_from_seller_dashboard(): void
    {
        $this->get(route('seller.index.get'))
            ->assertRedirect(route('seller.login.get'));
    }

    /**
     * @dataProvider sellerCoreRoutes
     */
    public function test_seller_can_access_core_pages(string $routeName, array $params = []): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->get(route($routeName, $params))
            ->assertOk();
    }

    public static function sellerCoreRoutes(): array
    {
        return [
            'dashboard'      => ['seller.index.get', []],
            'clients'        => ['seller.clients.get', []],
            'briefs hub'     => ['seller.briefs.get', []],
            'brands'         => ['seller.brands.get', []],
            'leads'          => ['seller.leads.get', []],
            'assigned leads' => ['seller.assigned-leads.get', []],
            'orders'         => ['seller.orders.get', []],
            'payments'       => ['seller.payments.get', []],
        ];
    }

    public function test_seller_cannot_access_brand_payments(): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->get(route('seller.brand-payments.get'))
            ->assertForbidden();
    }

    public function test_seller_can_open_renewals_for_own_order(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::create([
            'tenant_id'       => $brand->tenant_id ?? 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Smoke Test Service',
            'currency'        => 'USD',
            'unit_amount'     => 10_000,
            'amount_paid'     => 10_000,
            'status'          => 'paid',
            'order_type'      => 'original',
        ]);

        $this->actingAs($seller, 'seller')
            ->get(route('seller.renewed-orders.get', ['order' => $order->id]))
            ->assertOk();
    }

    public function test_seller_can_view_own_performance(): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->get(route('seller.seller-performance.get', ['seller' => $seller->id]))
            ->assertOk();
    }

    public function test_admin_cannot_access_seller_dashboard_without_seller_guard(): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('seller.index.get'))
            ->assertForbidden();
    }

    public function test_seller_post_logout_route_exists(): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->post(route('seller.logout'))
            ->assertRedirect(route('seller.login.get'));

        $this->assertGuest('seller');
    }
}
