@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Follow Up')

@section('content')
    <p>Hello <strong>{{ $lead->name ?? 'Valued Customer' }}</strong>,</p>

    <p>
        We noticed you reached out to us recently but have not received a response yet.
        Our sales team wanted to make sure your request did not go unanswered.
    </p>

    <p>
        If you still need assistance, simply reply to this email and we will get back to you as soon as possible.
        If your issue has already been resolved, please disregard this message.
    </p>

    <p><strong>Lead reference service:</strong> {{ data_get($lead->meta, 'service', 'N/A') }}</p>

    <p class="email-muted">Thank you for your patience and interest. We look forward to connecting with you soon.</p>
@endsection
