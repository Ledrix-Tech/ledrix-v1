<?php

namespace App\Services\Central;

use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantEmailVerification;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantUsageSnapshot;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    /**
     * Register a new tenant with trial.
     */
    public function register(array $data): Tenant
    {
        DB::connection('central')->beginTransaction();
        try {

            /*
            |--------------------------------------------------------------------------
            | Find Selected Package
            |--------------------------------------------------------------------------
            */
            $package = PackagePricing::where('slug', $data['pkg_slug'])
                ->where('status', 'active')
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Generate Unique Company Slug
            |--------------------------------------------------------------------------
            */

            $slug = Str::slug($data['name']);

            $originalSlug = $slug;
            $counter = 1;

            while (
                Tenant::where('slug', $slug)->exists()
            ) {

                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            /*
            |--------------------------------------------------------------------------
            | Generate Secure Password
            |--------------------------------------------------------------------------
            */

            $plainPassword = Str::password(
                16,
                true,
                true,
                false,
                false
            );

            /*
            |--------------------------------------------------------------------------
            | Create Tenant
            |--------------------------------------------------------------------------
            */

            $tenant = Tenant::create([

                'plan_id' => $package->id,

                'name' => $data['name'],

                'slug' => $slug,

                'email' => $data['email'],

                'password' => Hash::make($plainPassword),

                'phone' => $data['phone'],

                'country' => $data['country'],

                'address' => $data['address'],

                'website' => $data['website'],

                'billing_name' => $data['billing_name'],

                'billing_email' => $data['billing_email'],

                'billing_phone' => $data['billing_phone'],

                'billing_address' => $data['billing_address'],

                'trial_used' => true,

                'trial_ends_at' => now()->addDays($package->trial_days),

                'status' => 'pending_email',

                'registered_ip' => request()->ip(),

                'meta' => [
                    'registered_from' => 'website'
                ]

            ]);

            /*
            |--------------------------------------------------------------------------
            | Membership
            |--------------------------------------------------------------------------
            */

            TenantMembership::create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $package->id,
                'billing_cycle' => 'monthly',
                'amount'        => (float) $package->monthly_price,
                'currency'      => $package->currency ?? 'USD',
                'api_key'       => Str::random(64),
                'start_date'    => now()->toDateString(),
                'end_date'      => now()->addDays($package->trial_days)->toDateString(),
                'trial_start'   => now()->toDateString(),
                'trial_end'     => now()->addDays($package->trial_days)->toDateString(),
                'status'        => 'trialing',
                'renewed_by'    => 'tenant',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Usage Snapshot
            |--------------------------------------------------------------------------
            */

            TenantUsageSnapshot::create([
                'tenant_id'      => $tenant->id,
                'month_reset_at' => now()->startOfMonth(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Email Verification
            |--------------------------------------------------------------------------
            */

            TenantEmailVerification::generate(
                tenantId: $tenant->id,
                email: $tenant->email,
            );

            AuditLog::record(
                action: 'tenant.registered',
                tenantId: $tenant->id,
                actorType: 'tenant',
                actorId: $tenant->id,
                actorName: $tenant->name,
                context: ['description' => 'Company registered for free trial.'],
            );

            DB::connection('central')->commit();

            /*
            |--------------------------------------------------------------------------
            | Return Password Only Once
            |--------------------------------------------------------------------------
            */

            $tenant->plain_password = $plainPassword;

            return $tenant;

        } catch (Exception $e) {

            DB::connection('central')->rollBack();

            Log::error($e);

            throw $e;
        }
    }
}