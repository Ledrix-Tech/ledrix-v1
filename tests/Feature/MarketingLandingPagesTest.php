<?php

namespace Tests\Feature;

use App\Models\Central\DemoRequest;
use App\Models\Central\PackagePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class MarketingLandingPagesTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();

        SchemaEnsureDemoRequests::ensure();

        PackagePricing::query()->create([
            'name'         => 'Starter',
            'slug'         => 'starter',
            'monthly_price'=> 29,
            'yearly_price' => 290,
            'currency'     => 'USD',
            'trial_days'   => 14,
            'is_popular'   => true,
            'is_public'    => true,
            'sort_order'   => 1,
            'status'       => 'active',
        ]);
    }

    public function test_trial_landing_page_loads_and_links_to_register(): void
    {
        $this->get(route('lp.trial', [
            'utm_source'   => 'meta',
            'utm_campaign' => 'agency_crm',
        ]))
            ->assertOk()
            ->assertSee('Ledrix')
            ->assertSee(route('tenant.register.form', 'starter'), false);

        $this->assertSame('meta', session('marketing_attribution.utm_source'));
        $this->assertSame('/lp/agency-crm-trial', session('marketing_landing_path'));
    }

    public function test_demo_landing_stores_lead_and_redirects_to_thanks(): void
    {
        $this->get(route('lp.demo', ['utm_source' => 'google']))->assertOk();

        $this->post(route('demo.store'), [
            'name'        => 'Jordan Lee',
            'email'       => 'jordan@agency.test',
            'company'     => 'Close Co',
            'description' => 'Need seller routing',
            'source'      => 'lp_demo',
        ])->assertRedirect(route('lp.demo.thanks'));

        $this->get(route('lp.demo.thanks'))->assertOk()->assertSee('on the list');

        $demo = DemoRequest::query()->where('email', 'jordan@agency.test')->first();
        $this->assertNotNull($demo);
        $this->assertStringContainsString('utm_source=google', (string) $demo->description);
    }
}

final class SchemaEnsureDemoRequests
{
    public static function ensure(): void
    {
        if (\Illuminate\Support\Facades\Schema::connection('central')->hasTable('demo_requests')) {
            return;
        }

        \Illuminate\Support\Facades\Schema::connection('central')->create('demo_requests', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('demo_sent_at')->nullable();
            $table->timestamp('demo_expires_at')->nullable();
            $table->timestamps();
        });
    }
}
