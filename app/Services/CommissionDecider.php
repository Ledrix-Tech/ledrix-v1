<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;

class CommissionDecider
{
    public function creditSellerIdFor(Order $order): int
    {
        $mode = (string) config('commission.mode', 'default');

        $front = (int) ($order->front_seller_id ?: 0);
        $owner = (int) ($order->owner_seller_id ?: $order->seller_id ?: 0);

        // If no owner, fallback to front (or 0 as last resort)
        if (!$owner && $front) return $front;
        if (!$front) return $owner;

        return match ($mode) {
            'count'  => $this->decideByCount($order, $front, $owner),
            'amount' => $this->decideByAmount($order, $front, $owner),
            'days'   => $this->decideByDays($order, $front, $owner),
            default  => (($order->amount_paid ?? 0) > 0) ? $owner : $front,
        };
    }

    public function updateCountersAfterCredit(Order $order, int $creditedCents, int $creditedSellerId): void
    {
        $mode = (string) config('commission.mode', 'default');

        // ✅ Always set first_paid_at when the first successful payment is recorded (regardless of who got credited)
        if (!$order->first_paid_at) {
            $order->first_paid_at = now();
        }

        // ✅ Only advance front counters when front actually received credit
        $front = (int) ($order->front_seller_id ?: 0);
        if ($front && (int)$creditedSellerId === $front) {
            match ($mode) {
                'count'  => $order->front_credits_used = (int)$order->front_credits_used + 1,
                'amount' => $order->front_credited_cents = (int)$order->front_credited_cents + max(0, (int)$creditedCents),
                'days'   => null, // no counters needed beyond first_paid_at
                default  => null,
            };
        }

        $order->save();
    }

    private function decideByCount(Order $order, int $front, int $owner): int
    {
        $limit = (int) config('commission.count.payments', 1);
        $used  = (int) ($order->front_credits_used ?? 0);

        return ($used < $limit) ? $front : $owner;
    }

    private function decideByAmount(Order $order, int $front, int $owner): int
    {
        $cap  = (int) config('commission.amount.cents', 0);
        $used = (int) ($order->front_credited_cents ?? 0);

        if ($cap <= 0) return $owner; // if cap not set, don't give front by amount mode

        return ($used < $cap) ? $front : $owner;
    }

    private function decideByDays(Order $order, int $front, int $owner): int
    {
        $days = (int) config('commission.days.window', 0);
        if ($days <= 0) return $owner;

        // If never paid yet, first successful payment should credit front
        if (!$order->first_paid_at) return $front;

        $deadline = Carbon::parse($order->first_paid_at)->addDays($days);
        return now()->lte($deadline) ? $front : $owner;
    }
}
// class CommissionDecider
// {
//     public function creditSellerIdFor(Order $order): int
//     {
//         $mode = config('commission.mode');

//         $front = (int) ($order->front_seller_id ?: 0);
//         $owner = (int) ($order->owner_seller_id ?: $order->seller_id);

//         // No front recorded? fallback to owner.
//         if (!$front) return $owner ?: 0;

//         switch ($mode) {
//             case 'count':
//                 $limit = (int) config('commission.count.payments', 1);
//                 return ($order->front_credits_used < $limit) ? $front : $owner;

//             case 'amount':
//                 $cap = (int) config('commission.amount.cents', 0);
//                 return ($cap > 0 && $order->front_credited_cents < $cap) ? $front : $owner;

//             case 'days':
//                 $days = (int) config('commission.days.window', 0);
//                 if ($days <= 0) return $owner;
//                 if (!$order->first_paid_at) return $front; // first payment goes to front
//                 $deadline = Carbon::parse($order->first_paid_at)->addDays($days);
//                 return (now()->lte($deadline)) ? $front : $owner;

//             default:
//                 // safe default = your current behavior: first payment to front, rest to owner
//                 return ($order->amount_paid > 0) ? $owner : $front;
//         }
//     }

//     public function updateCountersAfterCredit(Order $order, int $creditedCents): void
//     {
//         $mode = config('commission.mode');

//         // Set first_paid_at if this is the first success
//         if (!$order->first_paid_at) {
//             $order->first_paid_at = now();
//         }

//         switch ($mode) {
//             case 'count':
//                 $order->front_credits_used += 1;
//                 break;

//             case 'amount':
//                 $order->front_credited_cents += max(0, (int)$creditedCents);
//                 break;

//             case 'days':
//                 // counters not required; first_paid_at already set
//                 break;
//         }

//         $order->save();
//     }
// }
