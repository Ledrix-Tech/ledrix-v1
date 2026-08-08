@php
    $needsPayment = $saasNeedsPayment ?? false;
    $expiresSoon = $saasExpiresSoon ?? false;
    $onTrial = $saasOnTrial ?? false;
    $daysUntil = (int) ($saasDaysUntilRenewal ?? 0);
    $trialDays = (int) ($saasTrialDaysLeft ?? 0);
    $membership = $saasMembership ?? null;
    $billingUrl = route('admin.org.billing');
@endphp

@if ($needsPayment)
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <strong>Subscription inactive</strong>
            <span class="d-block small mb-0">Payment is due or your plan has expired. Renew to keep CRM access for your team.</span>
        </div>
        <a href="{{ $billingUrl }}" class="btn btn-sm btn-warning">Renew billing</a>
    </div>
@elseif ($expiresSoon)
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <strong>Renews in {{ $daysUntil }} day(s)</strong>
            @if ($membership?->end_date)
                <span class="d-block small mb-0">Period ends {{ $membership->end_date->format('M d, Y') }}.</span>
            @endif
        </div>
        <a href="{{ $billingUrl }}" class="btn btn-sm btn-outline-primary">Renew early</a>
    </div>
@elseif ($onTrial)
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <strong>Free trial active</strong>
            <span class="d-block small mb-0">{{ $trialDays }} day(s) left — no card required until you subscribe.</span>
        </div>
        <a href="{{ $billingUrl }}" class="btn btn-sm btn-outline-primary">View billing</a>
    </div>
@endif
