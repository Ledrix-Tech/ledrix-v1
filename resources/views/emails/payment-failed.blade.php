@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Failed')

@section('content')
    <p>Hello <strong>{{ $clientName }}</strong>,</p>

    <p>
        Unfortunately, your recent payment attempt could not be completed.
        This is usually caused by an issue with your bank or card provider.
        You may try again using the payment link below.
    </p>

    <hr class="email-divider">

    <h3 class="email-heading" style="color:#db165b;">Payment failed</h3>

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
        <tr>
            <td><strong>Provider</strong></td>
            <td>{{ ucfirst($provider) }}</td>
        </tr>
        @if ($reason)
            <tr>
                <td><strong>Reason</strong></td>
                <td>{{ $reason }}</td>
            </tr>
        @endif
    </table>

    @if (isset($retryUrl) && $retryUrl)
        <a href="{{ $retryUrl }}" class="email-btn">Try Payment Again</a>

        <p class="email-muted" style="margin-top:16px;">
            If the button does not work, copy and paste this link into your browser:<br>
            <a href="{{ $retryUrl }}" class="email-link">{{ $retryUrl }}</a>
        </p>
    @endif
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        If you believe this is a mistake or your card was incorrectly declined,
        please contact your seller or reply to this email.<br><br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
