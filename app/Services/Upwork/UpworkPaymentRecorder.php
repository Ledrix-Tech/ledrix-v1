<?php

namespace App\Services\Upwork;

use App\Models\Upwork\UpworkOrder;
use App\Models\Upwork\UpworkPayment;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpworkPaymentRecorder
{
    /**
     * Idempotent: safe to call multiple times for the same provider intent.
     * Returns: [UpworkPayment $payment, UpworkOrder $order]
     */
    public function recordSucceededPayment(
        UpworkPaymentLink $link,
        string $provider,
        string $providerPaymentIntentId,
        int $reportedAmountCents,
        string $reportedCurrency,
        array $payload = [],
        ?string $sessionId = null
    ): array {
        return DB::transaction(function () use (
            $link,
            $provider,
            $providerPaymentIntentId,
            $reportedAmountCents,
            $reportedCurrency,
            $payload,
            $sessionId
        ) {
            /** @var UpworkOrder $order */
            $order = UpworkOrder::lockForUpdate()->findOrFail($link->order_id);

            // Normalize currency
            $reportedCurrency = strtoupper($reportedCurrency);
            $orderCurrency    = strtoupper($order->currency ?? $reportedCurrency);

            if ($orderCurrency !== $reportedCurrency) {
                Log::warning('Upwork payment currency mismatch', [
                    'order_id' => $order->id,
                    'order_currency' => $orderCurrency,
                    'reported_currency' => $reportedCurrency,
                    'intent' => $providerPaymentIntentId,
                ]);
                // You can abort here if you want strict:
                // throw new \RuntimeException('Currency mismatch');
            }

            // Remaining due (use balance_due, not unit_amount-amount_paid)
            $remaining = max(0, (int) $order->balance_due);
            $credit    = min(max(0, $reportedAmountCents), $remaining);

            // If already fully paid, just return existing payment if present
            $existing = UpworkPayment::where('provider', $provider)
                ->where('provider_payment_intent_id', $providerPaymentIntentId)
                ->first();

            if ($existing) {
                return [$existing, $order];
            }

            // Create payment (race-safe)
            try {
                $payment = UpworkPayment::create([
                    'order_id'                   => $order->id,
                    'payment_link_id'            => $link->id,
                    'amount'                     => $credit,
                    'currency'                   => $orderCurrency,
                    'status'                     => 'succeeded',
                    'provider'                   => $provider,
                    'provider_payment_intent_id' => $providerPaymentIntentId,
                    'payload'                    => $payload,
                ]);
            } catch (QueryException $e) {
                // Duplicate unique(provider, intent) during concurrency -> fetch and return
                $payment = UpworkPayment::where('provider', $provider)
                    ->where('provider_payment_intent_id', $providerPaymentIntentId)
                    ->first();

                if ($payment) {
                    return [$payment, $order];
                }

                throw $e;
            }

            // Roll-up order
            $order->amount_paid = (int) $order->amount_paid + $credit;
            $order->balance_due = max(0, (int) $order->unit_amount - (int) $order->amount_paid);

            if ($order->balance_due === 0) {
                $order->status  = 'paid';
                $order->paid_at = now();
            } else {
                $order->status = 'pending';
            }

            $order->provider_session_id        = $sessionId ?: $order->provider_session_id;
            $order->provider_payment_intent_id = $providerPaymentIntentId;
            $order->save();

            // Mark link used
            $link->update([
                'status'                     => 'paid',
                'is_active_link'             => false,
                'paid_at'                    => now(),
                'expires_at'                 => now(),
                'provider_session_id'        => $sessionId,
                'provider_payment_intent_id' => $providerPaymentIntentId,
            ]);

            // Log mismatch if Stripe reported more than remaining
            if ($reportedAmountCents > $remaining) {
                Log::warning('Upwork payment reported more than remaining due (capped)', [
                    'order_id' => $order->id,
                    'remaining' => $remaining,
                    'reported' => $reportedAmountCents,
                    'credited' => $credit,
                    'intent' => $providerPaymentIntentId,
                ]);
            }

            return [$payment, $order];
        });
    }
}

// class UpworkPaymentRecorder
// {
//     /**
//      * Idempotent: safe to call multiple times for the same provider intent.
//      */
//     public function recordSucceededPayment(
//         UpworkPaymentLink $link,
//         string $provider,
//         string $providerPaymentIntentId,
//         int $reportedAmountCents,
//         string $reportedCurrency,
//         array $payload = [],
//         ?string $sessionId = null
//     ): array {
//         return DB::transaction(function () use (
//             $link,
//             $provider,
//             $providerPaymentIntentId,
//             $reportedAmountCents,
//             $reportedCurrency,
//             $payload,
//             $sessionId
//         ) {
//             $order = UpworkOrder::lockForUpdate()->findOrFail($link->order_id);

//             // ✅ Idempotency guard
//             $existing = UpworkPayment::where('provider', $provider)
//                 ->where('provider_payment_intent_id', $providerPaymentIntentId)
//                 ->first();

//             if ($existing) {
//                 return [$existing, $order];
//             }

//             // Cap payment to remaining due (never over-credit)
//             $remaining = max(0, (int)$order->balance_due);
//             $credit = min($reportedAmountCents, $remaining);

//             $payment = UpworkPayment::create([
//                 'order_id'                   => $order->id,
//                 'payment_link_id'            => $link->id,
//                 'amount'                     => $credit,
//                 'currency'                   => strtoupper($reportedCurrency),
//                 'status'                     => 'succeeded',
//                 'provider'                   => $provider,
//                 'provider_payment_intent_id' => $providerPaymentIntentId,
//                 'payload'                    => $payload,
//             ]);

//             // Roll up order totals
//             $order->amount_paid = (int)$order->amount_paid + $credit;
//             $order->provider_session_id = $sessionId ?: $order->provider_session_id;
//             $order->provider_payment_intent_id = $providerPaymentIntentId;
//             $order->recomputeAndPersistStatus();
//             $order->save();

//             // Mark link used
//             $link->update([
//                 'status'                     => 'paid',
//                 'is_active_link'             => false,
//                 'paid_at'                    => now(),
//                 'expires_at'                 => now(),
//                 'provider_session_id'        => $sessionId,
//                 'provider_payment_intent_id' => $providerPaymentIntentId,
//             ]);

//             return [$payment, $order];
//         });
//     }
// }
