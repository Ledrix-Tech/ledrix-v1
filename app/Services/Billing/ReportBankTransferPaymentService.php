<?php

namespace App\Services\Billing;

use App\Mail\TenantBankTransferReportedMail;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ReportBankTransferPaymentService
{
    public function report(TenantPayment $payment, string $bankTxnId, ?string $tenantNote = null): TenantPayment
    {
        if ($payment->gateway !== 'bank_transfer') {
            throw new RuntimeException('Invalid payment method.');
        }

        if ($payment->status !== 'pending') {
            throw new RuntimeException('This payment is no longer awaiting transfer.');
        }

        $bankTxnId = trim($bankTxnId);

        if (strlen($bankTxnId) < 4) {
            throw new RuntimeException('Please enter a valid bank transaction or receipt ID.');
        }

        $payload = array_merge($payment->payload ?? [], [
            'customer_reported_txn_id' => $bankTxnId,
            'customer_reported_at'     => now()->toDateTimeString(),
            'customer_reported_note'   => $tenantNote ? trim($tenantNote) : null,
        ]);

        $payment->update(['payload' => $payload]);

        AuditLog::record(
            action: 'subscription.bank_transfer_reported',
            tenantId: $payment->tenant_id,
            actorType: 'tenant',
            actorId: $payment->tenant_id,
            actorName: $payment->tenant?->name ?? 'Tenant',
            context: [
                'subject_type' => 'tenant_payment',
                'subject_id'   => $payment->id,
                'description'  => 'Tenant reported bank transfer with transaction ID.',
                'after'        => [
                    'ledrix_reference' => $payment->transaction_id,
                    'bank_txn_id'      => $bankTxnId,
                    'amount'           => $payment->amount,
                    'currency'         => $payment->currency,
                ],
            ]
        );

        $this->notifyAdmin($payment->fresh(['tenant', 'invoice']));

        return $payment;
    }

    private function notifyAdmin(TenantPayment $payment): void
    {
        $email = config('services.bank_transfer.notify_email');

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new TenantBankTransferReportedMail($payment));
        } catch (Throwable $e) {
            Log::warning('Bank transfer reported email failed', [
                'payment_id' => $payment->id,
                'message'    => $e->getMessage(),
            ]);
        }
    }
}
