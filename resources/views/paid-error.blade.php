@extends('payments.layout')

@section('title', 'Payment Error')

@section('content')
    <div class="pay-card-header">
        <div class="pay-icon pay-icon--danger">✕</div>
        <h1>Payment failed</h1>
        <p>{{ $message ?? 'Something went wrong while processing your payment. Please try again.' }}</p>
    </div>

    <div class="pay-card-body">
        <div class="pay-actions">
            @if ($link?->last_issued_url)
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
