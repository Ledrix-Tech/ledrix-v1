@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Subscription renewal')

@section('content')
    <h2 class="email-heading">
        @if (($status ?? '') === 'success')
            Renewal successful
        @elseif (($status ?? '') === 'failed')
            Renewal failed
        @else
            Link expired
        @endif
    </h2>

    @if ($company)
        <p>Hi {{ $company->name }},</p>
    @endif

    <p>{{ $message ?? 'Thank you for using Ledrix.' }}</p>

    @if (($status ?? '') === 'success')
        <p class="email-muted">Your workspace subscription is active. You can sign in to your tenant portal anytime.</p>
    @elseif (($status ?? '') === 'expired')
        <p class="email-muted">Please contact support if you still need to renew your subscription.</p>
    @endif
@endsection
