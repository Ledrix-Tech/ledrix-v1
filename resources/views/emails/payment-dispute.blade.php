@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Dispute')

@section('content')
    <p>Hello <strong>{{ $clientName }}</strong>,</p>

    <p>
        This is an update regarding a <strong>payment dispute / chargeback</strong>
        related to your order.
    </p>

    <hr class="email-divider">

    <h3 class="email-heading">Dispute status: {{ strtoupper($stageLabel) }}</h3>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="140"><strong>Service</strong></td>
            <td>{{ $service }}</td>
        </tr>
        <tr>
            <td><strong>Brand</strong></td>
            <td>{{ $brandName }}</td>
        </tr>
        <tr>
            <td><strong>Order ID</strong></td>
            <td>#{{ $orderId }}</td>
        </tr>
        <tr>
            <td><strong>Provider</strong></td>
            <td>{{ ucfirst($provider) }}</td>
        </tr>
        <tr>
            <td><strong>Disputed amount</strong></td>
            <td>{{ $amount }}</td>
        </tr>
        @if ($reason)
            <tr>
                <td><strong>Details</strong></td>
                <td>{{ $reason }}</td>
            </tr>
        @endif
    </table>

    @if ($stage === 'created')
        <p class="email-muted">
            Your bank or card issuer has opened a dispute on this transaction.
            Our team will review and may contact you if more information is needed.
        </p>
    @elseif ($stage === 'updated')
        <p class="email-muted">
            The status of this dispute has been updated by the payment provider or bank.
        </p>
    @elseif ($stage === 'resolved')
        <p class="email-muted">
            This dispute has been marked as resolved by the bank or card network.
        </p>
    @endif
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        If you did not request this dispute or have any questions,
        please contact your seller or reply to this email immediately.<br><br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
