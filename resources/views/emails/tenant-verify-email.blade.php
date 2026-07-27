@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Verify your Ledrix account')

@section('content')
    <h2 class="email-heading">Verify your Ledrix account</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Thanks for signing up for Ledrix
        @if ($plan)
            on the <strong>{{ $plan->name }}</strong> plan
        @endif
        . Confirm your email to start your free trial.
    </p>

    <a href="{{ $verifyUrl }}" class="email-btn">Verify Email &amp; Start Trial</a>

    <p class="email-muted" style="margin-top:24px;">
        If the button does not work, copy and paste this link into your browser:<br>
        <a href="{{ $verifyUrl }}" class="email-link">{{ $verifyUrl }}</a>
    </p>
@endsection
