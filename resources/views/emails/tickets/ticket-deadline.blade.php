@extends('emails.layouts.ledrix')

@section('title', 'Ticket Deadline Notification')

@section('content')
    <p>Hello <strong>{{ $client->name }}</strong>,</p>

    <p>This is a reminder regarding your active ticket.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="120"><strong>Ticket</strong></td>
            <td>{{ $ticket->title ?? $ticket->subject ?? 'Support ticket' }}</td>
        </tr>
        <tr>
            <td><strong>Order ID</strong></td>
            <td>#{{ $order->id }}</td>
        </tr>
        <tr>
            <td><strong>Deadline</strong></td>
            <td>{{ $ticket->deadline?->toDayDateTimeString() ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ $ticket->status }}</td>
        </tr>
        <tr>
            <td><strong>Reminder</strong></td>
            <td>{{ ucfirst($stage) }}</td>
        </tr>
    </table>
@endsection
