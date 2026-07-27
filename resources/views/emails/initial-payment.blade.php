@extends('emails.layouts.ledrix')

@section('title', 'Payment Received')

@section('content')
    <h2 class="email-heading">Payment successfully received</h2>

    <p>Hello <strong>{{ $client->name ?? $order->buyer_name }}</strong>,</p>

    <p>Your payment for <strong>{{ $order->service_name }}</strong> has been received.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="120"><strong>Amount paid</strong></td>
            <td>{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</td>
        </tr>
        <tr>
            <td><strong>Order type</strong></td>
            <td>{{ ucfirst($order->order_type) }}</td>
        </tr>
        <tr>
            <td><strong>Paid at</strong></td>
            <td>{{ $payment->created_at->format('d M, Y h:i A') }}</td>
        </tr>
    </table>

    <p class="email-muted">Our team will begin processing your order immediately.</p>
@endsection
