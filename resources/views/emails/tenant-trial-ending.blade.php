@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Trial ending soon')

@section('content')
    <h2 class="email-heading">Your trial is ending soon</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Your Ledrix free trial ends on
        <strong>{{ $membership->trial_end?->format('M d, Y') ?? $tenant->trial_ends_at?->format('M d, Y') }}</strong>.
    </p>

    <p>
        To keep CRM access after your trial, you will receive a Payoneer payment request with your subscription amount and a unique payment reference.
    </p>

    <a href="{{ route('tenant.billing') }}" class="email-btn">View Billing</a>

    <p class="email-muted" style="margin-top:24px;">Questions? Reply to this email and our team will help.</p>
@endsection
