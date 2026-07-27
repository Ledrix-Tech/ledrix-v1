@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Link')

@section('content')
    <p>Hello <strong>{{ $recipient ?? ($lead->name ?? 'Valued Customer') }}</strong>,</p>

    <p>Thank you for choosing our service. To complete your order, please proceed with your payment below.</p>

    <hr class="email-divider">

    <h3 class="email-heading">Complete your payment</h3>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="100"><strong>Service</strong></td>
            <td>{{ $service }}</td>
        </tr>
        <tr>
            <td><strong>Brand</strong></td>
            <td>{{ $brandName }}</td>
        </tr>
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $amount }}</td>
        </tr>
        @isset($expiresAt)
            <tr>
                <td><strong>Expires</strong></td>
                <td>{{ $expiresAt->toDayDateTimeString() }}</td>
            </tr>
        @endisset
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
