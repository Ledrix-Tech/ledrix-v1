@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'We Received Your Request')

@section('content')
    <p>Hello <strong>{{ $lead->name ?? 'Valued Customer' }}</strong>,</p>

    <p>
        Thank you for contacting us.
        We have received your request and a member of our team will reach out to you shortly.
    </p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="140"><strong>Service requested</strong></td>
            <td>{{ data_get($lead->meta, 'service', 'Not specified') }}</td>
        </tr>
        <tr>
            <td><strong>Email</strong></td>
            <td>{{ $lead->email ?? 'Not provided' }}</td>
        </tr>
        <tr>
            <td><strong>Phone</strong></td>
            <td>{{ $lead->phone ?? 'Not provided' }}</td>
        </tr>
    </table>

    <p class="email-muted">We appreciate your interest and look forward to assisting you.</p>
@endsection
