<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\TestCase;

/**
 * Smoke tests: admin portal routes render without 5xx for allowed roles.
 *
 * @group smoke
 * @group admin
 */
class AdminSmokeTest extends TestCase
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

    public function test_admin_login_page_loads(): void
    {
        $this->get(route('admin.login.get'))
            ->assertOk();
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get(route('admin.index.get'))
            ->assertRedirect(route('admin.login.get'));
    }

    /**
     * @dataProvider adminCoreRoutes
     */
    public function test_admin_role_can_access_core_pages(string $routeName): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route($routeName))
            ->assertOk();
    }

    public static function adminCoreRoutes(): array
    {
        return [
            'dashboard'        => ['admin.index.get'],
            'clients'          => ['admin.clients.get'],
            'leads'            => ['admin.leads.get'],
            'assigned leads'   => ['admin.assigned-leads.get'],
            'orders'           => ['admin.orders.get'],
            'payments'         => ['admin.payments.get'],
            'brand payments'   => ['admin.brand-payments.get'],
            'brand payouts'    => ['admin.brand-payouts.get'],
        ];
    }

    /**
     * @dataProvider adminOnlyRoutes
     */
    public function test_admin_role_can_access_admin_only_pages(string $routeName): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route($routeName))
            ->assertOk();
    }

    public static function adminOnlyRoutes(): array
    {
        return [
            'brands'       => ['admin.brands.get'],
            'sellers'      => ['admin.sellers.get'],
            'account keys' => ['admin.account-keys.get'],
        ];
    }

    public function test_finance_role_can_access_brand_payment_reports(): void
    {
        $finance = $this->createAdmin(['role' => 'finance']);

        $this->actingAs($finance, 'admin')
            ->get(route('admin.brand-payments.get'))
            ->assertOk();
    }

    public function test_finance_role_is_redirected_from_dashboard(): void
    {
        $finance = $this->createAdmin(['role' => 'finance']);

        $this->actingAs($finance, 'admin')
            ->get(route('admin.index.get'))
            ->assertRedirect(route('admin.brand-payments.get'));
    }

    public function test_finance_role_cannot_access_admin_only_brands(): void
    {
        $finance = $this->createAdmin(['role' => 'finance']);

        $this->actingAs($finance, 'admin')
            ->get(route('admin.brands.get'))
            ->assertRedirect(route('admin.brand-payments.get'));
    }

    public function test_seller_logged_in_via_admin_guard_can_access_shared_admin_routes(): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->get(route('admin.leads.get'))
            ->assertOk();
    }

    public function test_finance_role_cannot_update_lead_status_via_compliance_route(): void
    {
        $finance = $this->createAdmin(['role' => 'finance']);
        ['brand' => $brand, 'lead' => $lead] = $this->createPaymentLeadGraph();

        $this->mockTenantFeaturesEnabled();

        $this->actingAs($finance, 'admin')
            ->post(route('lead.update-status'), [
                'lead_id' => $lead->id,
                'status'  => 'contacted',
            ])
            ->assertForbidden();
    }

    public function test_admin_post_logout_route_exists(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login.get'));

        $this->assertGuest('admin');
    }
}
