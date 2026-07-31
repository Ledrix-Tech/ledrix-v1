<?php

namespace Tests\Feature;

use Tests\TestCase;

class TenantRegistrationSuccessTest extends TestCase
{
    public function test_register_success_url_does_not_404_as_plan_slug(): void
    {
        $response = $this->get('/register/success');

        $response->assertRedirect(route('pricing.get'));
        $response->assertStatus(302);
    }
}
