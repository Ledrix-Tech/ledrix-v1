<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public Order $order,
        public string $provider,      // 'stripe' | 'paypal'
        public ?string $reason = null // optional note / reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $refundType = $this->payment->refund_status === 'full' ? 'full' : 'partial';

        $totalPaidCents    = (int) $this->payment->amount;
        $refundedCents     = (int) ($this->payment->refunded_amount ?: $this->payment->amount);
        $remainingCents    = max(0, $totalPaidCents - $refundedCents);

        $totalPaid         = number_format($totalPaidCents / 100, 2) . ' ' . $this->order->currency;
        $refundedAmount    = number_format($refundedCents / 100, 2) . ' ' . $this->order->currency;
        $remainingAmount   = $remainingCents > 0
            ? number_format($remainingCents / 100, 2) . ' ' . $this->order->currency
            : null;

        $clientName = $this->order->buyer_name
            ?? $this->order->client->name
            ?? 'Valued Customer';

        return (new MailMessage)
            ->subject(($refundType === 'full' ? 'Full Refund Processed – ' : 'Partial Refund Processed – ') . $this->order->service_name)
            ->view('emails.payment-refunded', [
                'clientName'      => $clientName,
                'service'         => $this->order->service_name,
                'brandName'       => $this->order->brand->brand_name ?? 'Our Team',
                'orderId'         => $this->order->id,
                'provider'        => $this->provider,
                'refundType'      => $refundType,
                'totalPaid'       => $totalPaid,
                'refundedAmount'  => $refundedAmount,
                'remainingAmount' => $remainingAmount,
                'reason'          => $this->reason,
            ]);
    }
}
