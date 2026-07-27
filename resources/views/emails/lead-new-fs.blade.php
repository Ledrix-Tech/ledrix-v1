@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'New Lead Assigned')

@section('content')
    <p>Hello <strong>{{ $seller->name }}</strong>,</p>

    <p>A new lead has just been assigned to you. Please follow up as soon as possible.</p>

    <h3 class="email-subheading">Lead details</h3>
    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="100"><strong>Name</strong></td>
            <td>{{ $lead->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Email</strong></td>
            <td>{{ $lead->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Phone</strong></td>
            <td>{{ $lead->phone ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Service</strong></td>
            <td>{{ data_get($lead->meta, 'service', 'N/A') }}</td>
        </tr>
        <tr>
            <td><strong>Message</strong></td>
            <td>{{ $lead->message ?? 'No message provided' }}</td>
        </tr>
    </table>

    <p class="email-muted">Quick follow-up increases conversion rate significantly.</p>
@endsection
