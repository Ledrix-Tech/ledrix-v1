<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Seller;
use App\Services\LeadScriptService;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class LeadStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(true);
        });

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('hasLeadPrediction')->andReturn(true);
            $mock->shouldReceive('enabled')->with('api_access', Mockery::any())->andReturn(true);
            $mock->shouldReceive('assertEnabled')->with('api_access', Mockery::any(), Mockery::any())->andReturnNull();
        });

        $this->mock(TenantLimitService::class, function ($mock) {
            $mock->shouldReceive('assertCanCreateLead')->andReturnNull();
        });
    }

    public function test_lead_can_be_stored_via_api(): void
    {
        Notification::fake();

        $brand = Brand::factory()->create([
            'brand_name' => 'Demo LLC',
            'brand_host' => 'example.com',
            'tenant_id'  => 1,
        ]);
        $seller = Seller::factory()->create(['brand_id' => $brand->id]);

        $assignerMock = Mockery::mock(\App\Services\LeadAssigner::class);
        $assignerMock->shouldReceive('assignNext')->andReturn($seller);
        App::instance(\App\Services\LeadAssigner::class, $assignerMock);

        $classifierMock = Mockery::mock(\App\Services\LeadClassifier::class);
        $classifierMock->shouldReceive('classify')->andReturn([
            'status' => 'real',
            'score'  => 90,
        ]);
        App::instance(\App\Services\LeadClassifier::class, $classifierMock);

        $payload = [
            'name'       => 'John Doe',
            'email'      => 'john@example.com',
            'phone'      => '1234567890',
            'service'    => 'Web Design',
            'message'    => 'Need a landing page',
            'url'        => 'https://example.com',
            'brand_key'  => $brand->public_form_token,
            'utm_source' => 'google',
            'timezone'   => 'UTC',
        ];

        $response = $this->postJson(route('crm.leads.post'), $payload, [
            'Origin' => 'https://example.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'email'  => 'john@example.com',
                    'status' => 'new',
                ],
            ]);

        $this->assertDatabaseHas('leads', [
            'email'     => 'john@example.com',
            'brand_id'  => $brand->id,
            'seller_id' => $seller->id,
            'status'    => 'new',
        ]);
    }

    public function test_lead_rejected_without_brand_key(): void
    {
        Brand::factory()->create([
            'brand_host' => 'example.com',
            'tenant_id'  => 1,
        ]);

        $response = $this->postJson(route('crm.leads.post'), [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'url'   => 'https://example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_field_mapping_is_applied_before_validation(): void
    {
        Notification::fake();

        $brand = Brand::factory()->create([
            'brand_host'    => 'mapped.com',
            'tenant_id'     => 1,
            'field_mapping' => [
                'name'  => 'full_name',
                'email' => 'user_email',
                'phone' => 'mobile',
            ],
        ]);
        $seller = Seller::factory()->create(['brand_id' => $brand->id]);

        App::instance(\App\Services\LeadAssigner::class, Mockery::mock(\App\Services\LeadAssigner::class, function ($mock) use ($seller) {
            $mock->shouldReceive('assignNext')->andReturn($seller);
        }));

        App::instance(\App\Services\LeadClassifier::class, Mockery::mock(\App\Services\LeadClassifier::class, function ($mock) {
            $mock->shouldReceive('classify')->andReturn(null);
        }));

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('hasLeadPrediction')->andReturn(false);
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
        });

        $response = $this->postJson(route('crm.leads.post'), [
            'full_name'  => 'Jane Mapped',
            'user_email' => 'jane@mapped.com',
            'mobile'     => '5551234567',
            'url'        => 'https://mapped.com',
            'brand_key'  => $brand->public_form_token,
        ], [
            'Origin' => 'https://mapped.com',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'email' => 'jane@mapped.com',
            'name'  => 'Jane Mapped',
        ]);
    }

    public function test_universal_script_is_served_for_brand(): void
    {
        $brand = Brand::factory()->create([
            'brand_host' => 'serve-test.com',
            'tenant_id'  => 1,
        ]);

        $response = $this->get(route('lead.script', ['host' => 'serve-test.com']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript');
        $this->assertStringContainsString($brand->public_form_token, $response->getContent());
        $this->assertStringContainsString(route('crm.leads.post'), $response->getContent());
        $this->assertStringContainsString('ledrixBound', $response->getContent());
    }

    public function test_admin_test_lead_can_be_stored(): void
    {
        Notification::fake();

        $brand = Brand::factory()->create([
            'brand_host' => 'admintest.com',
            'tenant_id'  => 1,
        ]);
        $seller = Seller::factory()->create(['brand_id' => $brand->id]);

        App::instance(\App\Services\LeadAssigner::class, Mockery::mock(\App\Services\LeadAssigner::class, function ($mock) use ($seller) {
            $mock->shouldReceive('assignNext')->andReturn($seller);
        }));

        App::instance(\App\Services\LeadClassifier::class, Mockery::mock(\App\Services\LeadClassifier::class, function ($mock) {
            $mock->shouldReceive('classify')->andReturn(null);
        }));

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('hasLeadPrediction')->andReturn(false);
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
        });

        $result = app(\App\Services\LeadIntakeService::class)->storeAdminTestLead($brand);

        $this->assertFalse($result['duplicate']);
        $this->assertDatabaseHas('leads', [
            'brand_id' => $brand->id,
            'email'    => $result['lead']->email,
        ]);

        Notification::assertNothingSent();
    }

    public function test_embed_snippet_includes_fallback_loader(): void
    {
        $brand = Brand::factory()->create([
            'brand_host' => 'fallback.com',
            'tenant_id'  => 1,
        ]);

        $snippet = app(LeadScriptService::class)->embedSnippetForBrand($brand);

        $this->assertStringContainsString('ledrix-fallback-src', $snippet);
        $this->assertStringContainsString('__ledrixActivateFallback', $snippet);
        $this->assertStringContainsString(route('lead.script', ['host' => 'fallback.com']), $snippet);
    }
}
