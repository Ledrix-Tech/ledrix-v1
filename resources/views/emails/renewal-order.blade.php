@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Renewal Order Created')

@section('content')
    <p>Hello <strong>{{ $client->name }}</strong>,</p>

    <p>
        Your renewal order has been successfully created.
        We appreciate your continued trust in our services.
    </p>

    <h3 class="email-subheading">Renewal order details</h3>
    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="100"><strong>Service</strong></td>
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
            <td><strong>Type</strong></td>
            <td>Renewal</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ ucfirst($order->status) }}</td>
        </tr>
    </table>

    <p class="email-muted">
        Your assigned project manager will soon send you a secure payment link to complete this renewal.
        You will be notified again once the payment link is ready.
    </p>
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        Thank you for continuing with us.<br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
