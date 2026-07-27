@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Link')

@section('content')
    <h2 class="email-heading">Complete your payment</h2>

    <p>Hello <strong>{{ $deal->buyer_name ?? $deal->client?->name ?? 'Valued Customer' }}</strong>,</p>

    <p>Please use the secure link below to complete payment for your order.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="100"><strong>Service</strong></td>
            <td>{{ $deal->service_name ?? 'Order payment' }}</td>
        </tr>
        @if ($deal->brand?->brand_name)
            <tr>
                <td><strong>Brand</strong></td>
                <td>{{ $deal->brand->brand_name }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Order ID</strong></td>
            <td>#{{ $deal->id }}</td>
        </tr>
    </table>

    <a href="{{ $url }}" class="email-btn">Pay Now</a>

    <p class="email-muted" style="margin-top:16px;">
        If the button does not work, copy and paste this link into your browser:<br>
        <a href="{{ $url }}" class="email-link">{{ $url }}</a>
    </p>
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        Thank you for your business!<br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
