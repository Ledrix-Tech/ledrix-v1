@extends('sellers.layout.layout')

@section('title', 'Seller | Generate Payment Link')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/generate-payment-link.css') }}">
@endpush

@section('sellers-content')

    @php
        $orderType = request('type');
        $isRenewal = $orderType === 'renewal';
        $isOrder = isset($order) && $order;
        $currency = old('currency', $isOrder ? $order->currency : 'USD');
        $serviceName = old('service_name', $isOrder ? $order->service_name ?? '' : ($lead->meta['service'] ?? $lead->service ?? ''));
        $totalDefault = $isOrder
            ? number_format(($order->unit_amount ?? 0) / 100, 2, '.', '')
            : old('total_amount');
        $dueCents = (int) ($isOrder ? $order->balance_due ?? 0 : 0);
        $payNowDefault = $isOrder ? number_format($dueCents / 100, 2, '.', '') : old('payable_amount');
        $hasStripe = $tenantHasStripe ?? tenantFeature('stripe');
        $hasPayPal = $tenantHasPayPal ?? tenantFeature('paypal');
        $hasMilestones = $tenantHasMilestonePayments ?? app(\App\Services\Tenant\TenantFeatureService::class)
            ->enabled('milestone_payments', (int) ($brand->tenant_id ?? 0) ?: null);
        $selectedProvider = old('provider', $hasStripe && ! $hasPayPal ? 'stripe' : (! $hasStripe && $hasPayPal ? 'paypal' : ''));
        $backUrl = url()->previous() !== url()->current() ? url()->previous() : route('seller.leads.get');
    @endphp

    <div class="crm-paylink-layout">
        <a href="{{ $backUrl }}" class="crm-paylink-back">
            <i class="bi bi-arrow-left"></i> Back to leads
        </a>

        <div class="crm-page-header">
            <div>
                <h1>Generate payment link</h1>
                <p>Create a Stripe or PayPal checkout link for this lead</p>
            </div>
        </div>

        @if (session('payment_link_url'))
            <div class="crm-paylink-success">
                <div class="crm-paylink-success-label">
                    <i class="bi bi-check-circle-fill"></i> Link generated
                </div>
                <div class="crm-paylink-url-row">
                    <a href="{{ session('payment_link_url') }}" target="_blank" rel="noopener"
                        class="crm-paylink-url">{{ session('payment_link_url') }}</a>
                    <button type="button" class="btn btn-sm btn-crm-green"
                        onclick="navigator.clipboard.writeText(@json(session('payment_link_url')))">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                    <a href="{{ session('payment_link_url') }}" target="_blank" rel="noopener"
                        class="btn btn-sm btn-crm-outline">
                        <i class="bi bi-box-arrow-up-right"></i> Open
                    </a>
                </div>
            </div>
        @endif

        <div class="crm-card">
            <div class="crm-paylink-lead">
                <div class="crm-paylink-lead-item">
                    <strong>Lead</strong>
                    {{ $lead->name }}
                </div>
                <div class="crm-paylink-lead-item">
                    <strong>Email</strong>
                    {{ $lead->email ?? '—' }}
                </div>
                <div class="crm-paylink-lead-item">
                    <strong>Brand</strong>
                    {{ $brand->brand_name }}
                </div>
                @if ($isOrder)
                    <div class="crm-paylink-lead-item">
                        <strong>Order</strong>
                        #{{ $order->id }}
                        @if ($dueCents > 0)
                            · Due {{ number_format($dueCents / 100, 2) }} {{ $order->currency }}
                        @else
                            · Paid in full
                        @endif
                    </div>
                @endif
            </div>

            <div class="crm-card-body">
                <form method="POST"
                    action="{{ route('generate-payment-link', [
                        'brand' => $brand->id,
                        'lead' => $lead->id,
                        'order' => $order->id ?? null,
                    ]) }}">
                    @csrf
                    <input type="hidden" name="order_type" value="{{ $isRenewal ? 'renewal' : 'original' }}">
                    @if ($isRenewal && $isOrder)
                        <input type="hidden" name="base_order_id" value="{{ $order->id }}">
                    @endif

                    <div class="crm-paylink-section">
                        <div class="crm-paylink-section-title">
                            <i class="bi bi-briefcase"></i> Service details
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" class="form-control crm-paylink-readonly"
                                    value="{{ $brand->brand_name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Currency</label>
                                <input type="text" class="form-control crm-paylink-readonly" name="currency"
                                    value="{{ $currency }}" maxlength="3" readonly required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Service name</label>
                                <input type="text" class="form-control crm-paylink-readonly" name="service_name"
                                    value="{{ $serviceName }}" readonly required>
                            </div>
                        </div>
                    </div>

                    <div class="crm-paylink-section">
                        <div class="crm-paylink-section-title">
                            <i class="bi bi-credit-card"></i> Payment method
                        </div>
                        @if (! $hasStripe && ! $hasPayPal)
                            <div class="alert alert-warning mb-0">
                                @if (! ($planHasStripe ?? false) && ! ($planHasPayPal ?? false))
                                    No payment providers are enabled for your plan. Contact your administrator.
                                @else
                                    No payment merchant is configured for this brand. Ask an admin to add Stripe/PayPal keys under Payment Accounts before generating a link.
                                @endif
                            </div>
                        @else
                            <div class="crm-paylink-provider mb-3">
                                @if ($hasStripe)
                                    <label class="crm-paylink-provider-option">
                                        <input type="radio" name="provider" value="stripe" required
                                            @checked($selectedProvider === 'stripe')>
                                        <span class="crm-paylink-provider-card">
                                            <span class="crm-paylink-provider-icon crm-paylink-provider-icon--stripe">
                                                <i class="fa fa-cc-stripe"></i>
                                            </span>
                                            <span>
                                                <span class="crm-paylink-provider-name">Stripe</span>
                                                <span class="crm-paylink-provider-desc d-block">Card payments</span>
                                            </span>
                                        </span>
                                    </label>
                                @endif
                                @if ($hasPayPal)
                                    <label class="crm-paylink-provider-option">
                                        <input type="radio" name="provider" value="paypal" required
                                            @checked($selectedProvider === 'paypal')>
                                        <span class="crm-paylink-provider-card">
                                            <span class="crm-paylink-provider-icon crm-paylink-provider-icon--paypal">
                                                <i class="fa fa-cc-paypal"></i>
                                            </span>
                                            <span>
                                                <span class="crm-paylink-provider-name">PayPal</span>
                                                <span class="crm-paylink-provider-desc d-block">PayPal checkout</span>
                                            </span>
                                        </span>
                                    </label>
                                @endif
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Expires in (hours)</label>
                                    <input type="number" class="form-control" name="expires_in_hours" min="1"
                                        max="3" value="{{ old('expires_in_hours', 3) }}" placeholder="1–3">
                                    <div class="crm-field-hint">Link valid for up to 3 hours</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="crm-paylink-section mb-0">
                        <div class="crm-paylink-section-title">
                            <i class="bi bi-currency-dollar"></i> Amounts
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total amount</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01"
                                    placeholder="e.g. 4000.00" value="{{ $totalDefault }}"
                                    {{ $isOrder ? 'readonly' : 'required' }}>
                                @if ($isOrder)
                                    <div class="crm-field-hint">Taken from the order</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pay now amount</label>
                                <input type="number" class="form-control" name="payable_amount" step="0.01"
                                    placeholder="e.g. 2000.00" value="{{ $payNowDefault }}" required
                                    {{ $isOrder && $dueCents === 0 ? 'readonly' : '' }}>
                                @if ($isOrder)
                                    <div class="crm-field-hint">
                                        Due: {{ number_format($dueCents / 100, 2) }} {{ $order->currency }}
                                        @if (! $hasMilestones)
                                            · Full balance required (milestones not on your plan)
                                        @endif
                                    </div>
                                @elseif (! $hasMilestones)
                                    <div class="crm-field-hint">Auto-matches total — partial payments require milestones on your plan</div>
                                @else
                                    <div class="crm-field-hint">Must be less than or equal to total</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="crm-paylink-footer">
                        <span class="crm-paylink-hint">
                            <i class="bi bi-shield-check"></i> Secure checkout link for client payment
                        </span>
                        @if ($isOrder && $dueCents === 0 && ! $isRenewal)
                            <button type="button" class="btn btn-crm-outline" disabled>Paid in full</button>
                        @elseif (! $hasStripe && ! $hasPayPal)
                            <button type="button" class="btn btn-crm-outline" disabled>Generate link</button>
                        @else
                            <button type="submit" class="btn btn-crm-primary">
                                <i class="bi bi-link-45deg me-1"></i> Generate link
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.crm-paylink-layout form');
            if (!form) return;

            const totalInput = form.querySelector('[name="total_amount"]');
            const payInput = form.querySelector('[name="payable_amount"]');
            const hasMilestones = @json($hasMilestones);
            const isOrder = @json($isOrder);
            const dueCents = @json($dueCents);

            function syncPayToTotal() {
                if (hasMilestones || isOrder || !totalInput || !payInput || totalInput.readOnly) {
                    return;
                }

                const total = parseFloat(totalInput.value || '0');
                if (total > 0) {
                    payInput.value = total.toFixed(2);
                }
            }

            syncPayToTotal();

            form.addEventListener('input', function(e) {
                if (e.target === totalInput) {
                    syncPayToTotal();
                }

                if (e.target === payInput && isOrder && dueCents > 0) {
                    const due = dueCents / 100;
                    const val = parseFloat(e.target.value || '0');
                    if (val > due) e.target.value = due.toFixed(2);
                    if (val < 0) e.target.value = '0.00';
                }

                if (e.target === payInput && !hasMilestones && !isOrder) {
                    syncPayToTotal();
                }
            });
        });
    </script>

@endsection
