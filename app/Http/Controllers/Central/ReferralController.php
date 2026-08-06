<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Referral;
use App\Models\Central\Tenant;
use App\Services\Billing\ReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = Referral::query()
            ->with(['referrer:id,name,email', 'referred:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $referrals = $query->paginate(20)->withQueryString();
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('central.pages.referrals', compact('referrals', 'tenants'));
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'referrer_tenant_id' => ['required', 'integer', Rule::exists(Tenant::class, 'id')],
            'reward_type'        => ['required', Rule::in(['credit', 'discount', 'cash'])],
            'reward_amount'      => ['required', 'numeric', 'min:0'],
            'currency'           => ['required', 'string', 'size:3'],
            'expires_at'         => ['nullable', 'date', 'after:today'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['referrer_tenant_id']);

        Referral::query()->create([
            'referrer_tenant_id' => $tenant->id,
            'referral_code'      => Referral::generateCode($tenant->name),
            'reward_type'        => $validated['reward_type'],
            'reward_amount'      => $validated['reward_amount'],
            'currency'           => strtoupper($validated['currency']),
            'status'             => 'pending',
            'expires_at'         => $validated['expires_at'] ?? now()->addMonths(6),
        ]);

        return back()->with('success', 'Referral code issued for ' . $tenant->name . '.');
    }

    public function reward(int $id, ReferralRewardService $rewards)
    {
        $referral = Referral::query()->findOrFail($id);

        if (! in_array($referral->status, ['converted', 'pending'], true)) {
            return back()->with('error', 'Only pending or converted referrals can be marked rewarded.');
        }

        try {
            $rewards->fulfill($referral);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Referral rewarded and applied to the referrer.');
    }

    public function expire(int $id)
    {
        $referral = Referral::query()->findOrFail($id);

        if ($referral->status === 'rewarded') {
            return back()->with('error', 'Rewarded referrals cannot be expired.');
        }

        $referral->expire();

        return back()->with('success', 'Referral expired.');
    }
}
