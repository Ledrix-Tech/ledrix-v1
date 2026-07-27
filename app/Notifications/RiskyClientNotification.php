<?php

namespace App\Notifications;

use App\Models\RiskyClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskyClientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RiskyClient $risk,
        public string $title = 'High Risk Client Alert'
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $client = $this->risk->client;
        $features = json_decode($this->risk->features, true);

        return (new MailMessage)
            ->subject("⚠️ {$this->title} — {$client->name}")
            ->view('emails.tickets.risky-clients', [
                'title' => $this->title,
                'body'  => "
                    <p>Hello,</p>
                    <p>A client has been flagged as <strong>{$this->risk->risk_level}</strong> churn risk.</p>
                    <table role=\"presentation\" class=\"email-info-table\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                        <tr><td width=\"120\"><strong>Client</strong></td><td>{$client->name}</td></tr>
                        <tr><td><strong>Email</strong></td><td>{$client->email}</td></tr>
                        <tr><td><strong>Risk score</strong></td><td>{$this->risk->risk_score}</td></tr>
                    </table>
                    <p><strong>Risk factors</strong></p>
                    <ul>
                        <li>Days since last order: {$features['days_since_last_order']}</li>
                        <li>Total orders: {$features['total_orders']}</li>
                        <li>Unpaid orders: {$features['unpaid_orders']}</li>
                        <li>Lead responses: {$features['lead_responses']}</li>
                    </ul>
                    <p>Please review and take required action.</p>
                ",
            ]);
    }
}
