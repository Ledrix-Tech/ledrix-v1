<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Seller;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class LeadStoreTest extends TestCase
{
    use RefreshDatabase;

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

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('hasLeadPrediction')->andReturn(true);
        });

        $payload = [
            'name'       => 'John Doe',
            'email'      => 'john@example.com',
            'phone'      => '1234567890',
            'service'    => 'Web Design',
            'message'    => 'Need a landing page',
            'url'        => 'https://example.com',
            'utm_source' => 'google',
            'timezone'   => 'UTC',
        ];

        $response = $this->postJson(route('crm.leads.post'), $payload);

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

        $this->assertDatabaseHas('clients', [
            'email' => 'john@example.com',
            'name'  => 'John Doe',
        ]);
    }
}
