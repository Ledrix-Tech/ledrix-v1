<?php

namespace App\Services\Billing;

use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;

class CancelStaleSubscriptionPaymentsService
{
    /**
     * Cancel abandoned pending payments (e.g. after a successful activation or stale checkout attempts).
     *
     * @return int Number of payments cancelled
     */
    public function cancelForTenant(int $tenantId, ?int $exceptPaymentId = null): int
    {
        $payments = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->when($exceptPaymentId, fn ($q) => $q->where('id', '!=', $exceptPaymentId))
            ->get();

        foreach ($payments as $payment) {
            $this->cancelPayment($payment, 'superseded');
        }

        return $payments->count();
    }

    public function cancelPayment(TenantPayment $payment, string $reason = 'abandoned'): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status'  => 'failed',
            'payload' => array_merge($payment->payload ?? [], [
                'cancelled_at'    => now()->toDateTimeString(),
                'cancelled_reason'=> $reason,
            ]),
        ]);

        if ($payment->invoice && $payment->invoice->status === 'issued') {
            $payment->invoice->update([
                'status' => 'void',
                'notes'  => trim(($payment->invoice->notes ?? '') . ' Voided — payment not completed.'),
            ]);
        }
    }
}
