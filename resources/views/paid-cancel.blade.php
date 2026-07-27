@extends('payments.layout')

@section('title', 'Payment Cancelled')

@section('content')
    <div class="pay-card-header">
        <div class="pay-icon pay-icon--warning">!</div>
        <h1>Payment cancelled</h1>
        <p>You cancelled the payment before completing it. No charges were made.</p>
    </div>

    <div class="pay-card-body">
        <div class="pay-actions">
            @if ($link->last_issued_url ?? null)
                <a href="{{ $link->last_issued_url }}" class="pay-btn pay-btn--primary">Try again</a>
            @endif
            @php
                $brandUrl = trim((string) ($brand->brand_url ?? ''));
                $brandUrl = $brandUrl ? (str_starts_with($brandUrl, 'http') ? $brandUrl : 'https://' . $brandUrl) : null;
            @endphp
            @if ($brandUrl)
                <a href="{{ $brandUrl }}" class="pay-btn pay-btn--ghost">Return to website</a>
            @endif
        </div>
    </div>
@endsection
