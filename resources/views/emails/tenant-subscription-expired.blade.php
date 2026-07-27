@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Subscription expired')

@section('content')
    <h2 class="email-heading">Your subscription has expired</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Your Ledrix subscription period ended on
        <strong>{{ $membership->end_date?->format('M d, Y') }}</strong>.
        CRM access is paused until payment is received.
    </p>

    @php
        $graceDays = (int) config('subscription.past_due_grace_days', 7);
    @endphp

    <p>
        You have a {{ $graceDays }}-day grace period to renew before your workspace is fully locked.
        Pay online from billing — your subscription reactivates automatically once payment clears.
    </p>

    <a href="{{ route('tenant.billing') }}" class="email-btn">Pay &amp; restore access</a>

    <p class="email-muted" style="margin-top:24px;">
        Need help? Reply to this email and our team will assist you.
    </p>
@endsection
