<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDisputeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public Order $order,
        public string $provider,        // 'stripe' | 'paypal'
        public string $stage,           // created | updated | resolved
        public ?string $reason = null   // optional details / codes
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $stageLabel = match ($this->stage) {
            'created' => 'Dispute Opened',
            'updated' => 'Dispute Updated',
            'resolved' => 'Dispute Resolved',
            default   => ucfirst($this->stage),
        };

        $clientName = $this->order->buyer_name
            ?? $this->order->client->name
            ?? 'Valued Customer';

        $amount = number_format($this->payment->amount / 100, 2) . ' ' . $this->order->currency;

        return (new MailMessage)
            ->subject("Payment Dispute – {$stageLabel} – {$this->order->service_name}")
            ->view('emails.payment-dispute', [
                'clientName'  => $clientName,
                'service'     => $this->order->service_name,
                'brandName'   => $this->order->brand->brand_name ?? 'Our Team',
                'orderId'     => $this->order->id,
                'provider'    => $this->provider,
                'amount'      => $amount,
                'stage'       => $this->stage,
                'stageLabel'  => $stageLabel,
                'reason'      => $this->reason,
            ]);
    }
}
