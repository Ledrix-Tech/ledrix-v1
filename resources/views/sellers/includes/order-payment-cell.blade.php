@php
    $due = (int) ($order->balance_due ?? 0);
    $sellerUser = auth('seller')->user();
    $adminUser = auth('admin')->check();
    $isAdmin = $adminUser !== null;
    $isFront = $sellerUser && ($sellerUser->role ?? $sellerUser->is_seller) === 'front_seller';
    $sameBrand = $sellerUser && (int) $sellerUser->brand_id === (int) $order->brand_id;
    $canGenerateAsFront = $isFront && $sameBrand;
    $canGenerateAsAdmin = $isAdmin;
    $canGenerate =
        ($canGenerateAsFront || $canGenerateAsAdmin) &&
        ($tenantHasPayments ?? tenantHasPayments()) &&
        $due > 0 &&
        $order->status !== 'paid';

    $linkParams = [
        'brand' => $order->brand_id,
        'lead' => $order->lead_id,
        'order' => $order->id,
    ];
    if (!empty($renewalType)) {
        $linkParams['type'] = $renewalType;
    }
@endphp

<div class="crm-payment-cell">
    @if ($canGenerate)
        <span class="crm-status crm-status-warning">
            <i class="bi bi-clock"></i>
            Due: {{ number_format($due / 100, 2) }} {{ $order->currency ?? 'USD' }}
        </span>
        <a href="{{ route('generate-link-form', $linkParams) }}"
            class="btn btn-sm btn-crm-primary crm-generate-link">
            <i class="bi bi-link-45deg"></i> Generate link
        </a>
    @else
        @if ($due <= 0)
            <span class="crm-status crm-status-success">
                <i class="bi bi-check-circle-fill"></i> Paid in full
            </span>
        @else
            <span class="crm-status crm-status-neutral">
                <i class="bi bi-lock-fill"></i> Pending
            </span>
        @endif
    @endif
</div>
