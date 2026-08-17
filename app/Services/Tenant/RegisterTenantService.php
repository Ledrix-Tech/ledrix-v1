<?php

namespace App\Services\Tenant;

use App\Mail\TenantVerifyEmail;
use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\Referral;
use App\Models\Central\Tenant;
use App\Models\Central\TenantEmailVerification;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantUsageSnapshot;
use App\Support\MarketingAttribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RegisterTenantService
{
    /**
     * @return array{tenant: Tenant, verification: TenantEmailVerification}
     */
    public function register(array $data): array
    {
        $package = PackagePricing::query()
            ->where('slug', $data['pkg_slug'])
            ->where('status', 'active')
            ->firstOrFail();

        DB::connection('central')->beginTransaction();

        try {
            $slug = $this->uniqueSlug($data['name']);
            $billingCycle = $data['billing_cycle'] ?? 'monthly';
            $trialDays = (int) $package->trial_days;
            $trialEndsAt = now()->addDays($trialDays);
            $billingCurrency = \App\Services\Billing\TenantBillingRegion::currencyFromCountry($data['country'] ?? null);
            $amount = app(\App\Services\Billing\SubscriptionPricingService::class)
                ->resolveAmount($package, $billingCycle, $billingCurrency);

            $tenant = Tenant::create([
                'plan_id'         => $package->id,
                'name'            => $data['name'],
                'slug'            => $slug,
                'email'           => $data['email'],
                'password'        => Hash::make($data['password']),
                'phone'           => $data['phone'] ?? null,
                'country'         => $data['country'],
                'address'         => $data['address'] ?? null,
                'website'         => $data['website'] ?? null,
                'billing_name'    => $data['billing_name'],
                'billing_email'   => $data['billing_email'],
                'billing_phone'   => $data['billing_phone'] ?? null,
                'billing_address' => $data['billing_address'],
                'preferred_billing_currency' => $billingCurrency,
                'trial_used'      => true,
                'trial_ends_at'   => $trialEndsAt,
                'status'          => 'inactive',
                'registered_ip'   => request()->ip(),
                'meta'            => array_filter([
                    'registered_from' => MarketingAttribution::source(),
                    'billing_cycle'   => $billingCycle,
                    'landing_path'    => MarketingAttribution::landingPath(),
                    'attribution'     => MarketingAttribution::all() ?: null,
                ], fn ($value) => $value !== null && $value !== []),
            ]);

            $attribution = MarketingAttribution::all();

            TenantMembership::create([
                'tenant_id'    => $tenant->id,
                'plan_id'      => $package->id,
                'billing_cycle'=> $billingCycle,
                'amount'       => $amount,
                'currency'     => $billingCurrency,
                'api_key'      => $this->uniqueApiKey(),
                'start_date'   => now()->toDateString(),
                'end_date'     => $trialEndsAt->toDateString(),
                'trial_start'  => now()->toDateString(),
                'trial_end'    => $trialEndsAt->toDateString(),
                'status'       => 'trialing',
                'renewed_by'   => 'tenant',
                'conversion_source' => MarketingAttribution::conversionSource(),
                'meta'         => array_filter([
                    'trial_days'  => $trialDays,
                    'attribution' => $attribution ?: null,
                ], fn ($value) => $value !== null && $value !== []),
            ]);

            TenantUsageSnapshot::create([
                'tenant_id'      => $tenant->id,
                'month_reset_at' => now()->startOfMonth(),
            ]);

            $verification = TenantEmailVerification::generate(
                tenantId: $tenant->id,
                email: $tenant->email,
            );

            $referralCode = isset($data['referral_code']) ? strtoupper(trim((string) $data['referral_code'])) : '';
            if ($referralCode !== '') {
                $referral = Referral::query()
                    ->where('referral_code', $referralCode)
                    ->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($referral && (int) $referral->referrer_tenant_id !== (int) $tenant->id) {
                    $referral->convert($tenant->id);
                }
            }

            AuditLog::record(
                action: 'tenant.registered',
                tenantId: $tenant->id,
                actorType: 'tenant',
                actorId: $tenant->id,
                actorName: $tenant->name,
                context: [
                    'subject_type' => 'tenant',
                    'subject_id'   => $tenant->id,
                    'description'  => "Registered on {$package->name} plan with {$trialDays}-day trial.",
                    'after'        => [
                        'plan_slug'     => $package->slug,
                        'billing_cycle' => $billingCycle,
                        'trial_ends_at' => $trialEndsAt->toDateTimeString(),
                        'referral_code' => $referralCode !== '' ? $referralCode : null,
                    ],
                ]
            );

            DB::connection('central')->commit();

            $this->sendVerificationEmail($tenant, $verification);

            return [
                'tenant'       => $tenant->fresh(['plan', 'activeMembership']),
                'verification' => $verification,
            ];
        } catch (Throwable $e) {
            DB::connection('central')->rollBack();

            Log::error('Tenant registration failed', [
                'email'   => $data['email'] ?? null,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to complete registration. Please try again.');
        }
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueApiKey(): string
    {
        do {
            $key = Str::random(64);
        } while (TenantMembership::where('api_key', $key)->exists());

        return $key;
    }

    private function sendVerificationEmail(Tenant $tenant, TenantEmailVerification $verification): void
    {
        $verifyUrl = route('tenant.verify-email', ['token' => $verification->token]);

        try {
            Mail::to($tenant->email)->send(new TenantVerifyEmail($tenant, $verifyUrl, $tenant->plan));
        } catch (Throwable $e) {
            Log::warning('Tenant verification email failed to send', [
                'tenant_id' => $tenant->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
