<?php

namespace Tests\Feature;

use App\Models\Central\SuperAdmin;
use App\Models\Central\SuperAdminInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class SuperAdminSecurityTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        Mail::fake();
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        $this->get(route('super-admin.referrals.get'))
            ->assertRedirect(route('super-admin.login.get'));
    }

    public function test_support_cannot_access_pricing_or_billing_settings(): void
    {
        $support = $this->makeAdmin('support');

        $this->actingAs($support, 'super_admin')
            ->get(route('super-admin.pricing-packages.get'))
            ->assertForbidden();

        $this->actingAs($support, 'super_admin')
            ->get(route('super-admin.billing-settings.get'))
            ->assertForbidden();
    }

    public function test_support_can_access_referrals_list(): void
    {
        $support = $this->makeAdmin('support');

        $this->actingAs($support, 'super_admin')
            ->get(route('super-admin.referrals.get'))
            ->assertOk();
    }

    public function test_admin_can_access_pricing_but_cannot_invite_team(): void
    {
        $admin = $this->makeAdmin('admin');

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.pricing-packages.get'))
            ->assertOk();

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.invite.send'), [
                'name'  => 'New Support',
                'email' => 'new-support@example.com',
                'role'  => 'support',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_send_invite_and_accept_creates_account(): void
    {
        $owner = $this->makeAdmin('owner');

        $this->actingAs($owner, 'super_admin')
            ->post(route('super-admin.invite.send'), [
                'name'  => 'Invited Admin',
                'email' => 'invited-admin@example.com',
                'role'  => 'admin',
            ])
            ->assertRedirect();

        $invite = SuperAdminInvite::query()->where('email', 'invited-admin@example.com')->first();
        $this->assertNotNull($invite);
        $this->assertTrue($invite->isUsable());

        auth('super_admin')->logout();

        $this->get(route('super-admin.invite.accept', $invite->token))
            ->assertOk();

        $this->post(route('super-admin.invite.accept.post', $invite->token), [
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('super-admin.index.get'));

        $created = SuperAdmin::query()->where('email', 'invited-admin@example.com')->first();
        $this->assertNotNull($created);
        $this->assertAuthenticatedAs($created, 'super_admin');
        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_expired_invite_cannot_be_accepted(): void
    {
        $invite = SuperAdminInvite::query()->create([
            'token'      => 'expired-token-' . uniqid(),
            'name'       => 'Late',
            'email'      => 'late@example.com',
            'role'       => 'support',
            'expires_at' => now()->subHour(),
        ]);

        $this->get(route('super-admin.invite.accept', $invite->token))
            ->assertNotFound();
    }

    public function test_inactive_super_admin_is_logged_out(): void
    {
        $admin = $this->makeAdmin('admin', 'inactive');

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.referrals.get'))
            ->assertRedirect(route('super-admin.login.get'));
    }

    private function makeAdmin(string $role, string $status = 'active'): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name'     => ucfirst($role) . ' User',
            'email'    => $role . '-' . uniqid() . '@example.com',
            'password' => Hash::make('Password1!'),
            'role'     => $role,
            'status'   => $status,
        ]);
    }
}
