@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Subscription activated')

@section('content')
    <h2 class="email-heading">Subscription activated</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>We received your JazzCash payment and your Ledrix subscription is now active.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="140"><strong>Amount paid</strong></td>
            <td>PKR {{ number_format((float) $payment->amount, 0) }}</td>
        </tr>
        <tr>
            <td><strong>Reference</strong></td>
            <td>{{ $payment->transaction_id }}</td>
        </tr>
    </table>

    <a href="{{ route('tenant.dashboard') }}" class="email-btn">Go to Dashboard</a>
@endsection
