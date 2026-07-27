@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Your Ledrix payment receipt')

@section('content')
    <h2 class="email-heading">Payment received</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>Thank you for your payment.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="120"><strong>Amount</strong></td>
            <td>{{ strtoupper($payment->currency) }} {{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Transaction</strong></td>
            <td>{{ $payment->transaction_id }}</td>
        </tr>
    </table>
@endsection
