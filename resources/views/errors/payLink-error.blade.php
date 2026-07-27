@extends('payments.layout')

@section('title', 'Link Unavailable')

@section('content')
    <div class="pay-card-header">
        <div class="pay-icon pay-icon--danger">⏱</div>
        <h1>Link unavailable</h1>
        <p>{{ $message ?? 'This payment link is not active or has expired.' }}</p>
    </div>

    <div class="pay-card-body">
        <p style="text-align:center; color: var(--text-muted); font-size: 0.9rem; margin: 0 0 20px;">
            Please contact your seller or support team to request a new payment link.
        </p>
        <div class="pay-actions">
            <a href="{{ route('client.login.get') }}" class="pay-btn pay-btn--ghost">Go to portal</a>
        </div>
    </div>
@endsection
