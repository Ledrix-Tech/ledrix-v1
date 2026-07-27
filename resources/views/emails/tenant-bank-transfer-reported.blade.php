@extends('emails.layouts.ledrix', ['contentAlign' => 'left'])

@section('title', 'Bank transfer to verify')

@section('content')
    <h2 class="email-heading">Tenant reported a bank transfer</h2>

    <p>Verify this payment on your Meezan statement before confirming in super admin.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;background:#f9fafb;border-radius:8px;padding:8px 12px;">
        <tr>
            <td width="180"><strong>Tenant</strong></td>
            <td>{{ $payment->tenant?->name ?? '—' }} ({{ $payment->tenant?->email }})</td>
        </tr>
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ strtoupper($payment->currency) }} {{ number_format((float) $payment->amount, 0) }}</td>
        </tr>
        <tr>
            <td><strong>Ledrix reference</strong></td>
            <td><code>{{ $payment->transaction_id }}</code></td>
        </tr>
        <tr>
            <td><strong>Bank txn ID (tenant)</strong></td>
            <td><code>{{ $payment->payload['customer_reported_txn_id'] ?? '—' }}</code></td>
        </tr>
        @if (! empty($payment->payload['customer_reported_note']))
            <tr>
                <td><strong>Tenant note</strong></td>
                <td>{{ $payment->payload['customer_reported_note'] }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Invoice</strong></td>
            <td>{{ $payment->invoice?->invoice_number ?? '—' }}</td>
        </tr>
    </table>

    <p class="email-muted" style="margin-top:20px;">
        Only confirm after the amount and reference match your bank statement. Never confirm from tenant submission alone without verification.
    </p>
@endsection
