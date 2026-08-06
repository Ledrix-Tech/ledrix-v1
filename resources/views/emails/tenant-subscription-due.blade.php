@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Subscription payment due')

@section('content')
    <h2 class="email-heading">Subscription payment due</h2>

    <p>Hi {{ $tenant->name }},</p>

    @if ($payment->gateway === 'bank_transfer')
        <p>Your Ledrix trial has ended. Please transfer the amount below to our Meezan Bank account to restore full CRM access.</p>
    @else
        <p>Your Ledrix trial has ended. Please complete your subscription payment to restore full CRM access.</p>
    @endif

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;background:#f9fafb;border-radius:8px;padding:8px 12px;">
        <tr>
            <td width="160"><strong>Invoice</strong></td>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Plan</strong></td>
            <td>{{ $invoice->plan_name ?? $tenant->plan?->name ?? 'Ledrix Plan' }}</td>
        </tr>
        @php
            $dueCurrency = strtoupper($invoice->currency ?? $payment->currency ?? 'USD');
            $dueDecimals = $dueCurrency === 'USD' ? 2 : 0;
        @endphp
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $dueCurrency }} {{ number_format((float) ($invoice->total_amount ?? $payment->amount), $dueDecimals) }}</td>
        </tr>
        <tr>
            <td><strong>Payment reference</strong></td>
            <td><code>{{ $payment->transaction_id }}</code></td>
        </tr>
        @if ($payment->gateway === 'bank_transfer')
            @php $bank = config('services.bank_transfer.pkr', []); @endphp
            <tr>
                <td><strong>Bank</strong></td>
                <td>{{ $bank['bank_name'] ?? 'Meezan Bank' }}</td>
            </tr>
            <tr>
                <td><strong>Account</strong></td>
                <td>{{ $bank['account_title'] ?? '—' }} · {{ $bank['account_number'] ?? '—' }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Due by</strong></td>
            <td>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</td>
        </tr>
    </table>

    <a href="{{ route('tenant.billing.invoice.show', $invoice->id) }}" class="email-btn">View invoice</a>
    <p style="margin-top:16px;"><a href="{{ route('tenant.billing') }}">Or open billing</a></p>
@endsection
