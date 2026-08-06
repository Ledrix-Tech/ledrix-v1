<?php

namespace App\Services\Billing;

use App\Models\Central\AuditLog;
use App\Models\Central\Referral;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReferralRewardService
{
    /**
     * Apply referral reward to the referrer tenant and mark the referral rewarded.
     */
    public function fulfill(Referral $referral): void
    {
        DB::connection('central')->transaction(function () use ($referral) {
            $locked = Referral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, ['pending', 'converted'], true)) {
                throw new RuntimeException('Only pending or converted referrals can be rewarded.');
            }

            if ($locked->isExpired()) {
                throw new RuntimeException('This referral has expired and cannot be rewarded.');
            }

            $referrer = Tenant::query()
                ->whereKey($locked->referrer_tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $currency = strtoupper((string) $locked->currency);
            $amount = round((float) $locked->reward_amount, 2);

            $meta = is_array($referrer->meta) ? $referrer->meta : [];
            $description = match ($locked->reward_type) {
                'credit' => $this->applyCredit($meta, $currency, $amount, $referrer),
                'discount' => $this->applyDiscount($meta, $currency, $amount, $referrer),
                'cash' => "Cash payout of {$currency} {$amount} marked for manual processing.",
                default => throw new RuntimeException('Unknown reward type.'),
            };

            $locked->reward();

            $actor = Auth::guard('super_admin')->user();

            AuditLog::record(
                action: 'referral.rewarded',
                tenantId: $referrer->id,
                actorType: $actor ? 'super_admin' : 'system',
                actorId: $actor?->id,
                actorName: $actor?->name ?? 'System',
                context: [
                    'subject_type' => 'referral',
                    'subject_id'   => $locked->id,
                    'description'  => $description,
                    'after'        => [
                        'reward_type'   => $locked->reward_type,
                        'reward_amount' => $amount,
                        'currency'      => $currency,
                        'code'          => $locked->referral_code,
                    ],
                ]
            );
        });
    }

    /**
     * Reduce invoice amount using referral discount then billing credits.
     *
     * @return array{amount: float, original_amount: float, discount_applied: float, credit_applied: float, notes: list<string>}
     */
    public function applyToInvoiceAmount(Tenant $tenant, float $amount, string $currency): array
    {
        $currency = strtoupper($currency);
        $original = $amount;
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        $discountApplied = 0.0;
        $creditApplied = 0.0;
        $notes = [];
        $metaChanged = false;

        $discount = $meta['referral_discount'] ?? null;
        if (is_array($discount) && strtoupper((string) ($discount['currency'] ?? '')) === $currency) {
            $type = $discount['type'] ?? 'amount';
            $value = (float) ($discount['value'] ?? 0);

            if ($value > 0) {
                $discountApplied = $type === 'percent'
                    ? round($amount * min(100, $value) / 100, 2)
                    : round(min($amount, $value), 2);

                $amount = max(0, round($amount - $discountApplied, 2));
                $notes[] = $type === 'percent'
                    ? "Referral discount {$value}% (−{$currency} {$discountApplied})"
                    : "Referral discount (−{$currency} {$discountApplied})";
            }

            unset($meta['referral_discount']);
            $metaChanged = true;
        }

        $credits = $meta['billing_credits'] ?? [];
        if (! is_array($credits)) {
            $credits = [];
        }

        $available = (float) ($credits[$currency] ?? 0);
        if ($available > 0 && $amount > 0) {
            $creditApplied = round(min($amount, $available), 2);
            $amount = max(0, round($amount - $creditApplied, 2));
            $remaining = round($available - $creditApplied, 2);

            if ($remaining > 0) {
                $credits[$currency] = $remaining;
            } else {
                unset($credits[$currency]);
            }

            $meta['billing_credits'] = $credits;
            $notes[] = "Billing credit (−{$currency} {$creditApplied})";
            $metaChanged = true;
        }

        if ($metaChanged) {
            $tenant->forceFill(['meta' => $meta])->save();
        }

        return [
            'amount'           => $amount,
            'original_amount'  => $original,
            'discount_applied' => $discountApplied,
            'credit_applied'   => $creditApplied,
            'notes'            => $notes,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function creditBalances(Tenant $tenant): array
    {
        $credits = $tenant->meta['billing_credits'] ?? [];

        if (! is_array($credits)) {
            return [];
        }

        $balances = [];
        foreach ($credits as $currency => $amount) {
            $amount = round((float) $amount, 2);
            if ($amount > 0) {
                $balances[strtoupper((string) $currency)] = $amount;
            }
        }

        return $balances;
    }

    private function applyCredit(array &$meta, string $currency, float $amount, Tenant $referrer): string
    {
        $credits = $meta['billing_credits'] ?? [];
        if (! is_array($credits)) {
            $credits = [];
        }

        $credits[$currency] = round(((float) ($credits[$currency] ?? 0)) + $amount, 2);
        $meta['billing_credits'] = $credits;
        $referrer->forceFill(['meta' => $meta])->save();

        return "Applied {$currency} {$amount} account credit to {$referrer->name}.";
    }

    private function applyDiscount(array &$meta, string $currency, float $amount, Tenant $referrer): string
    {
        // Always treat SA "discount" rewards as a fixed currency amount on the next invoice.
        // Percent discounts must be issued explicitly via meta type=percent elsewhere.
        $meta['referral_discount'] = [
            'type'     => 'amount',
            'value'    => $amount,
            'currency' => $currency,
        ];
        $referrer->forceFill(['meta' => $meta])->save();

        return "Queued {$currency} {$amount} one-shot referral discount for {$referrer->name}.";
    }
}
