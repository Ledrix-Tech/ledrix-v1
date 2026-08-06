<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Models\Central\Referral;
use App\Services\Billing\ReferralRewardService;
use App\Services\Billing\TenantBillingRegion;
use Illuminate\Http\Request;

class TenantReferralController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(ReferralRewardService $rewards)
    {
        $tenant = $this->organizationTenant();

        $referrals = Referral::query()
            ->with('referred:id,name,email')
            ->where('referrer_tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $credits = $rewards->creditBalances($tenant);
        $billingCurrency = TenantBillingRegion::currencyForTenant($tenant);
        $defaultPackage = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->first(['slug']);

        return $this->organizationView('referrals', [
            'tenant'          => $tenant,
            'referrals'       => $referrals,
            'credits'         => $credits,
            'billingCurrency' => $billingCurrency,
            'defaultSlug'     => $defaultPackage?->slug,
            'pendingDiscount' => is_array($tenant->meta['referral_discount'] ?? null)
                ? $tenant->meta['referral_discount']
                : null,
        ]);
    }

    public function issue(Request $request)
    {
        $tenant = $this->organizationTenant();

        $activePending = Referral::query()
            ->where('referrer_tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        if ($activePending >= 5) {
            return back()->with('error', 'You already have 5 active referral codes. Share those first.');
        }

        $currency = TenantBillingRegion::currencyForTenant($tenant);

        Referral::query()->create([
            'referrer_tenant_id' => $tenant->id,
            'referral_code'      => Referral::generateCode($tenant->name),
            'reward_type'        => 'credit',
            'reward_amount'      => 50,
            'currency'           => $currency,
            'status'             => 'pending',
            'expires_at'         => now()->addMonths(6),
        ]);

        return back()->with('success', 'New referral code created. Share it to earn account credit.');
    }
}
