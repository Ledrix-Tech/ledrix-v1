<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ConfirmManualSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionPaymentController extends Controller
{
    public function pending()
    {
        $payments = TenantPayment::with(['tenant:id,name,email,slug', 'plan:id,name', 'invoice'])
            ->whereIn('gateway', ['payoneer', 'bank_transfer'])
            ->where('status', 'pending')
            ->orderByDesc(DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.customer_reported_at')), '')"))
            ->orderByDesc('created_at')
            ->paginate(20);

        $automatedPayments = TenantPayment::with(['tenant:id,name,email,slug', 'plan:id,name', 'invoice'])
            ->whereIn('gateway', ['stripe', 'payfast'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return view('central.pages.subscription-payments', compact('payments', 'automatedPayments'));
    }

    public function confirm(Request $request, int $paymentId, ConfirmManualSubscriptionService $confirmService)
    {
        $payment = TenantPayment::with(['tenant', 'membership', 'invoice'])
            ->whereIn('gateway', ['payoneer', 'bank_transfer'])
            ->findOrFail($paymentId);

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $note = $validated['admin_note'] ?? null;

        if ($payment->gateway === 'bank_transfer') {
            $parts = array_filter([
                'Verified on Meezan statement.',
                $payment->customerReportedTxnId()
                    ? 'Tenant bank txn ID: ' . $payment->customerReportedTxnId()
                    : null,
                'Ledrix ref: ' . $payment->transaction_id,
                $note,
            ]);
            $note = implode(' ', $parts);
        }

        try {
            $confirmService->confirm($payment, $note);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment confirmed and subscription activated.');
    }
}
