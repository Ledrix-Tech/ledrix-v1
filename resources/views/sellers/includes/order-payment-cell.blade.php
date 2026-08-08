@php
    $due = (int) ($order->balance_due ?? 0);
    $sellerUser = auth('seller')->user();
    $adminUser = auth('admin')->check();
    $isAdmin = $adminUser !== null;
    $isFront = $sellerUser && ($sellerUser->role ?? $sellerUser->is_seller) === 'front_seller';
    $sameBrand = $sellerUser && (int) $sellerUser->brand_id === (int) $order->brand_id;
    $canGenerateAsFront = $isFront && $sameBrand;
    $canGenerateAsAdmin = $isAdmin;
    $canGenerateAsPm = false;
    if ($sellerUser && ($sellerUser->role ?? $sellerUser->is_seller) === 'project_manager' && $order->lead) {
        $canGenerateAsPm = \Illuminate\Support\Facades\Gate::forUser($sellerUser)
            ->allows('createPaymentLink', $order->lead);
    }

    $activeLink = $order->relationLoaded('latestPaymentLink') ? $order->latestPaymentLink : null;
    if (! $activeLink && $order->id) {
        $activeLink = $order->latestPaymentLink()->first();
    }
    $hasActiveUnusedLink = $activeLink && $activeLink->isActiveLink();

    $isRenewalOrder = ($order->order_type ?? '') === 'renewal';
    $isPaidOriginal = ($order->order_type ?? '') === 'original' && $due <= 0;
    $wantsRenewal = ! empty($renewalType);

    $canGenerate =
        ($canGenerateAsFront || $canGenerateAsAdmin || $canGenerateAsPm)
        && ($tenantHasPayments ?? tenantHasPayments())
        && ! $hasActiveUnusedLink
        && (
            ($due > 0 && $order->status !== 'paid' && ($isRenewalOrder || ! $isPaidOriginal))
            || ($isPaidOriginal && $wantsRenewal)
        );

    if ($isRenewalOrder && $due > 0) {
        $generateUrl = route('generate-link-form', [
            'brand' => $order->brand_id,
            'lead'  => $order->lead_id,
            'order' => $order->id,
            'type'  => 'renewal',
        ]);
    } elseif ($isPaidOriginal && $wantsRenewal) {
        $generateUrl = route('renew-order-link', [
            'brand' => $order->brand_id,
            'lead'  => $order->lead_id,
            'order' => $order->id,
            'type'  => 'renewal',
        ]);
    } else {
        $generateUrl = route('generate-link-form', [
            'brand' => $order->brand_id,
            'lead'  => $order->lead_id,
            'order' => $order->id,
        ]);
    }
@endphp

<div class="crm-payment-cell">
    @if ($hasActiveUnusedLink)
        <span class="crm-status crm-status-warning">
            <i class="bi bi-link-45deg"></i> Active link outstanding
        </span>
        @if ($activeLink?->last_issued_url)
            <button type="button" class="btn btn-sm btn-crm-outline copyBtn mt-1"
                data-url="{{ $activeLink->last_issued_url }}">
                <i class="bi bi-clipboard"></i> Copy link
            </button>
        @endif
    @elseif ($canGenerate)
        @if ($due > 0)
            <span class="crm-status crm-status-warning">
                <i class="bi bi-clock"></i>
                Due: {{ number_format($due / 100, 2) }} {{ $order->currency ?? 'USD' }}
            </span>
        @endif
        <a href="{{ $generateUrl }}" class="btn btn-sm btn-crm-primary crm-generate-link">
            <i class="bi bi-link-45deg"></i>
            {{ ($isPaidOriginal && $wantsRenewal) ? 'Renewal link' : 'Generate link' }}
        </a>
    @else
        @if ($due <= 0 && ! $isPaidOriginal)
            <span class="crm-status crm-status-success">
                <i class="bi bi-check-circle-fill"></i> Paid in full
            </span>
        @elseif ($isPaidOriginal && ! $wantsRenewal)
            <span class="crm-status crm-status-success">
                <i class="bi bi-check-circle-fill"></i> Original paid
            </span>
        @else
            <span class="crm-status crm-status-neutral">
                <i class="bi bi-lock-fill"></i> Pending
            </span>
        @endif
    @endif
</div>
