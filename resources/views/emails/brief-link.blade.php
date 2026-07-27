@extends('emails.layouts.ledrix', ['contentAlign' => 'left'])

@section('title', 'Brief Form Required')

@section('content')
    <p>Hello <strong>{{ $client->name }}</strong>,</p>

    <p>We need your <strong>project brief</strong> to begin working on your order.</p>

    <h3 class="email-subheading">Order details</h3>
    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="120"><strong>Service</strong></td>
            <td>{{ $order->service_name }}</td>
        </tr>
        <tr>
            <td><strong>Brand</strong></td>
            <td>{{ $brand }}</td>
        </tr>
        <tr>
            <td><strong>Order ID</strong></td>
            <td>#{{ $order->id }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ ucfirst($order->status) }}</td>
        </tr>
    </table>

    <p>Please click the secure link below to submit your questionnaire:</p>

    <p style="text-align:center;">
        <a href="{{ $briefUrl }}" target="_blank" class="email-btn">Fill Out Brief Form</a>
    </p>

    <p class="email-muted">
        This link is valid until
        <strong>{{ $order->brief->brief_token_expires_at ?? 'N/A' }}</strong>.
        Once submitted, your project manager will begin working on your order.
    </p>
@endsection
