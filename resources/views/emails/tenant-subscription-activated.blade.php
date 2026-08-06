@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment received')

@section('content')
    @php
        $currency = strtoupper($invoice->currency ?? $payment->currency ?? 'USD');
        $decimals = $currency === 'USD' ? 2 : 0;
        $amount = (float) ($invoice->total_amount ?? $payment->amount);
        $gateway = ucfirst(str_replace('_', ' ', $payment->gateway ?? 'payment'));
    @endphp

    <h2 class="email-heading">Payment received</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Thank you — we received your {{ $gateway }} payment and your Ledrix subscription is now active.
        @if ($invoice)
            Your paid invoice is ready below.
        @endif
    </p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        @if ($invoice)
            <tr>
                <td width="160"><strong>Invoice</strong></td>
                <td>{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td><strong>Plan</strong></td>
                <td>{{ $invoice->plan_name ?? $tenant->plan?->name ?? 'Ledrix Plan' }}</td>
            </tr>
            <tr>
                <td><strong>Billing cycle</strong></td>
                <td>{{ ucfirst($invoice->billing_cycle ?? $payment->billing_cycle ?? 'monthly') }}</td>
            </tr>
        @endif
        <tr>
            <td width="160"><strong>Amount paid</strong></td>
            <td>{{ $currency }} {{ number_format($amount, $decimals) }}</td>
        </tr>
        <tr>
            <td><strong>Payment method</strong></td>
            <td>{{ $gateway }}</td>
        </tr>
        <tr>
            <td><strong>Reference</strong></td>
            <td>{{ $payment->transaction_id }}</td>
        </tr>
        @if ($invoice?->paid_at)
            <tr>
                <td><strong>Paid at</strong></td>
                <td>{{ $invoice->paid_at->format('M d, Y H:i') }}</td>
            </tr>
        @endif
    </table>

    @if ($invoice)
        <a href="{{ $invoiceUrl }}" class="email-btn">View invoice</a>
    @else
        <a href="{{ $billingUrl }}" class="email-btn">Open billing</a>
    @endif
@endsection
