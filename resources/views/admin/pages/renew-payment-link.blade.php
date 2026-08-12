@extends('admin.layout.layout')

@section('title', 'Admin | Renew Payment Link')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/generate-payment-link.css') }}">
@endpush

@section('admin-content')

    @php
        $orderType = $orderType ?? request('type');
        $isRenewal = ($orderType ?? '') === 'renewal';
        $isOrder = isset($order) && $order;
        $currency = old('currency', $isOrder ? $order->currency : 'USD');
        $serviceName = old('service_name', $isOrder ? $order->service_name ?? '' : ($lead->meta['service'] ?? ''));
        $dueCents = (int) ($isOrder ? $order->balance_due ?? 0 : 0);
        $selectedProvider = old('provider', '');
        $hasStripe = $tenantHasStripe ?? tenantFeature('stripe');
        $hasPayPal = $tenantHasPayPal ?? tenantFeature('paypal');
        if ($selectedProvider === '' && $hasStripe && ! $hasPayPal) {
            $selectedProvider = 'stripe';
        } elseif ($selectedProvider === '' && ! $hasStripe && $hasPayPal) {
            $selectedProvider = 'paypal';
        }
    @endphp

    <div class="crm-paylink-layout">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.orders.get') }}"
            class="crm-paylink-back">
            <i class="bi bi-arrow-left"></i> Back to orders
        </a>

        <div class="crm-page-header">
            <div>
                <h1>Renew payment link</h1>
                <p>Generate a renewal checkout link for this order</p>
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
                    <strong>Brand</strong>
                    {{ $brand->brand_name }}
                </div>
                <div class="crm-paylink-lead-item">
                    <strong>Order type</strong>
                    {{ $orderType ?? 'renewal' }}
                </div>
            </div>

            <div class="crm-card-body">
                <form method="POST"
                    action="{{ route('generate-payment-link', [
                        'brand' => $brand->id,
                        'lead' => $lead->id,
                        'order' => $order->id ?? null,
                    ]) }}">
                    @csrf
                    <input type="hidden" name="order_type" value="{{ $orderType ?? 'renewal' }}">
                    @if ($isOrder)
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
                                    value="{{ $currency }}" readonly required>
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
                                    No payment providers are enabled for this plan.
                                @else
                                    No payment merchant is configured for this brand. Add Stripe/PayPal keys under
                                    <strong>Admin → Payment Accounts</strong> before generating a link.
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
                        @endif
                    </div>

                    <div class="crm-paylink-section mb-0">
                        <div class="crm-paylink-section-title">
                            <i class="bi bi-currency-dollar"></i> Amounts
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total amount</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01"
                                    placeholder="e.g. 4000.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Pay now amount</label>
                                <input type="number" class="form-control" name="payable_amount" step="0.01"
                                    placeholder="e.g. 2000.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Expires in (hours)</label>
                                <input type="number" class="form-control" name="expires_in_hours" min="1"
                                    max="3" value="{{ old('expires_in_hours', 3) }}">
                            </div>
                        </div>
                    </div>

                    <div class="crm-paylink-footer">
                        <span class="crm-paylink-hint">
                            <i class="bi bi-shield-check"></i> Renewal checkout link
                        </span>
                        @if (! $hasStripe && ! $hasPayPal)
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

@endsection
