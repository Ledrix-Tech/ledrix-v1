@extends('emails.layouts.ledrix')

@section('title', 'New Ticket Created')

@section('content')
    <p>Hello <strong>{{ $client->name }}</strong>,</p>

    <p>A new support ticket has been created for your order.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="100"><strong>Order ID</strong></td>
            <td>#{{ $order->id }}</td>
        </tr>
        <tr>
            <td><strong>Subject</strong></td>
            <td>{{ $ticket->subject }}</td>
        </tr>
        <tr>
            <td><strong>Priority</strong></td>
            <td>{{ $ticket->priority }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ $ticket->status }}</td>
        </tr>
        <tr>
            <td><strong>Description</strong></td>
            <td>{{ $ticket->description }}</td>
        </tr>
    </table>

    @if (! empty($url) && $url !== '#')
        <p style="text-align:center;">
            <a href="{{ $url }}" class="email-btn">View Ticket</a>
        </p>
    @endif
@endsection
