@extends('emails.layouts.ledrix')

@section('title', 'New Lead Assigned To You')

@section('content')
    <p>Hello <strong>{{ $pm->name }}</strong>,</p>

    <p>
        A new lead has been assigned to you by <strong>{{ $assigner->name ?? 'System' }}</strong>.
        Please follow up and provide the required service or support.
    </p>

    <h3 class="email-subheading">Lead details</h3>
    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="140"><strong>Name</strong></td>
            <td>{{ $lead->name }}</td>
        </tr>
        <tr>
            <td><strong>Email</strong></td>
            <td>{{ $lead->email }}</td>
        </tr>
        <tr>
            <td><strong>Phone</strong></td>
            <td>{{ $lead->phone }}</td>
        </tr>
        <tr>
            <td><strong>Service requested</strong></td>
            <td>{{ data_get($lead->meta, 'service', 'N/A') }}</td>
        </tr>
        <tr>
            <td><strong>Message</strong></td>
            <td>{{ $lead->message ?? 'No message provided' }}</td>
        </tr>
    </table>

    <p class="email-muted">Please ensure timely follow-up to maintain an excellent client experience.</p>
@endsection
