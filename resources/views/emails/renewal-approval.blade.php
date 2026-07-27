@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Approve your Ledrix subscription renewal')

@section('content')
    <h2 class="email-heading">Subscription renewal ready</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>Your Ledrix workspace renewal is ready for approval.</p>

    <a href="{{ route('super-renew.approve', ['token' => $renewalRequest->token]) }}" class="email-btn">Approve Renewal</a>

    <p class="email-muted" style="margin-top:20px;">This link expires in 24 hours.</p>
@endsection
