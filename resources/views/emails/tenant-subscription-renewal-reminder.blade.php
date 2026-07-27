@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Subscription renewal reminder')

@section('content')
    <h2 class="email-heading">
        @if ($daysLeft <= 1)
            Your subscription renews tomorrow
        @else
            Your subscription renews in {{ $daysLeft }} days
        @endif
    </h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Your Ledrix {{ $tenant->plan?->name ?? 'plan' }} subscription
        @if ($membership->billing_cycle === 'yearly')
            (yearly billing)
        @else
            (monthly billing)
        @endif
        expires on <strong>{{ $membership->end_date?->format('M d, Y') }}</strong>.
    </p>

    <p>
        Renew now to keep uninterrupted CRM access for your team.
        You can pay online from your billing page — activation is automatic.
    </p>

    <a href="{{ route('tenant.billing') }}" class="email-btn">Renew subscription</a>

    <p class="email-muted" style="margin-top:24px;">
        Questions about billing? Reply to this email and our team will help.
    </p>
@endsection
