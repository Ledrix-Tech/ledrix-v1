<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Services\LeadScriptService;
use Tests\TestCase;

class LeadScriptServiceTest extends TestCase
{
    public function test_default_field_mapping_contains_core_fields(): void
    {
        $mapping = LeadScriptService::defaultFieldMapping();

        $this->assertSame('name', $mapping['name']);
        $this->assertSame('email', $mapping['email']);
        $this->assertSame('phone', $mapping['phone']);
    }

    public function test_universal_script_embeds_brand_key_and_endpoint(): void
    {
        $brand = new Brand([
            'id'                => 99,
            'brand_host'        => 'acme.com',
            'public_form_token' => 'test-brand-token',
            'field_mapping'     => ['name' => 'full_name'],
        ]);

        $script = app(LeadScriptService::class)->renderUniversalScript($brand);

        $this->assertStringContainsString('test-brand-token', $script);
        $this->assertStringContainsString('#lead-form', $script);
        $this->assertStringContainsString('full_name', $script);
    }
}
