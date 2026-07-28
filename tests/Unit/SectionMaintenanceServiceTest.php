<?php

namespace Tests\Unit;

use App\Services\SectionMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SectionMaintenanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionMaintenanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SectionMaintenanceService::class);
        Cache::flush();
    }

    public function test_admin_section_can_be_toggled(): void
    {
        $this->assertFalse($this->service->isDown('admin'));

        $this->service->down('admin', 'Updating admin panel');

        $this->assertTrue($this->service->isDown('admin'));
        $this->assertSame('Updating admin panel', $this->service->message('admin'));

        $this->service->up('admin');

        $this->assertFalse($this->service->isDown('admin'));
    }

    public function test_resolves_admin_and_front_paths(): void
    {
        $adminRequest = Request::create('/admin/login', 'GET');
        $frontRequest = Request::create('/pricing', 'GET');
        $tenantRequest = Request::create('/sign-in', 'GET');

        $this->assertSame('admin', $this->service->sectionForRequest($adminRequest));
        $this->assertSame('front', $this->service->sectionForRequest($frontRequest));
        $this->assertNull($this->service->sectionForRequest($tenantRequest));
    }

    public function test_api_paths_bypass_section_maintenance(): void
    {
        $this->service->down('front');

        $webhookRequest = Request::create('/api/webhooks/stripe', 'POST');

        $this->assertNull($this->service->sectionForRequest($webhookRequest));
    }
}
