@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Refund')

@section('content')
    <p>Hello <strong>{{ $clientName }}</strong>,</p>

    <p>
        This is to confirm that a
        <strong>{{ $refundType === 'full' ? 'full refund' : 'partial refund' }}</strong>
        has been processed for your order.
    </p>

    <hr class="email-divider">

    <h3 class="email-heading">Refund details</h3>

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
            <td><strong>Total paid</strong></td>
            <td>{{ $totalPaid }}</td>
        </tr>
        <tr>
            <td><strong>Refunded amount</strong></td>
            <td>{{ $refundedAmount }}</td>
        </tr>
        @if ($refundType === 'partial' && $remainingAmount)
            <tr>
                <td><strong>Remaining applied</strong></td>
                <td>{{ $remainingAmount }}</td>
            </tr>
        @endif
        @if ($reason)
            <tr>
                <td><strong>Note</strong></td>
                <td>{{ $reason }}</td>
            </tr>
        @endif
    </table>

    <p class="email-muted">
        The refund may take a few business days to appear on your statement,
        depending on your bank or card provider.
    </p>
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        If you have any questions about this refund, please contact your seller or reply to this email.<br><br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
