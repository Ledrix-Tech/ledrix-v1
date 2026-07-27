@extends('admin.layout.layout')

@section('title', 'Admin | Payment Accounts')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/account-keys.css') }}">
@endpush

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Payment Accounts</h1>
            <p>Stripe & PayPal keys per brand and CRM module. Secret values are encrypted and never shown in full.</p>
        </div>
        <div class="crm-page-actions">
            <button type="button" class="btn btn-crm-teal" data-toggle="modal" data-target="#addKeys">
                <i class="bi bi-plus-lg me-1"></i> Add keys
            </button>
        </div>
    </div>

    @if ($keys->isEmpty())
        <div class="crm-keys-empty">
            <i class="bi bi-key"></i>
            <h3>No payment accounts yet</h3>
            <p>Connect Stripe or PayPal credentials for each brand to start accepting payments.</p>
            <button type="button" class="btn btn-crm-primary" data-toggle="modal" data-target="#addKeys">
                <i class="bi bi-plus-lg me-1"></i> Add first account
            </button>
        </div>
    @else
        <div class="crm-keys-grid">
            @foreach ($keys as $key)
                @php
                    $brand = $key->brand;
                    $iconUrl =
                        $brand &&
                        filter_var($brand->brand_url, FILTER_VALIDATE_URL) &&
                        !preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $brand->brand_url)
                            ? 'https://www.google.com/s2/favicons?sz=64&domain=' .
                                parse_url($brand->brand_url, PHP_URL_HOST)
                            : ($brand->brand_url ?? '');
                    $moduleLabel = strtoupper($key->module ?? '—');
                @endphp
                <form action="{{ route('admin.account-keys-update', $key->id) }}" method="POST" class="crm-key-card">
                    @csrf
                    <input type="hidden" name="brand_id" value="{{ $key->brand_id }}">

                    <div class="crm-key-card-head">
                        <div class="crm-key-brand">
                            @if ($iconUrl)
                                <img src="{{ $iconUrl }}" alt="" class="crm-key-favicon">
                            @else
                                <div class="crm-key-favicon d-flex align-items-center justify-content-center">
                                    <i class="bi bi-globe2 text-muted"></i>
                                </div>
                            @endif
                            <div>
                                <h3>{{ $brand->brand_name ?? 'Global account' }}</h3>
                                <p class="crm-key-brand-url">{{ $brand->brand_url ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="crm-key-badges">
                            <span class="crm-status crm-status-info">{{ $moduleLabel }}</span>
                            <span class="crm-status {{ $key->status === 'active' ? 'crm-status-success' : 'crm-status-danger' }}">
                                {{ ucfirst($key->status ?? 'inactive') }}
                            </span>
                        </div>
                    </div>

                    <div class="crm-key-body">
                        <div class="crm-key-section">
                            <div class="field-full mb-2">
                                <label class="form-label fw-semibold small text-muted">CRM module</label>
                                <select name="module" class="form-select" required>
                                    <option value="" disabled>— Select module —</option>
                                    <option value="ppc" @selected($key->module == 'ppc')>PPC</option>
                                    <option value="upwork" @selected($key->module == 'upwork')>Upwork</option>
                                </select>
                            </div>
                        </div>

                        <div class="crm-key-section">
                            <div class="crm-key-section-title crm-key-section-title--stripe">
                                <i class="fa fa-cc-stripe"></i> Stripe
                            </div>
                            <div class="crm-key-fields">
                                <div class="field-full">
                                    <label>Publishable key</label>
                                    <input type="text" name="stripe_publishable_key" class="form-control"
                                        value="{{ $key->stripe_publishable_key }}" autocomplete="off">
                                </div>
                                <div class="field-full">
                                    <label>Secret key</label>
                                    @if ($key->hasStripeSecret())
                                        <div class="crm-secret-hint">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Configured: <code>{{ $key->maskedStripeSecret() }}</code></span>
                                        </div>
                                    @endif
                                    <input type="password" name="stripe_secret_key" class="form-control"
                                        placeholder="{{ $key->hasStripeSecret() ? 'Leave blank to keep current secret' : 'Enter Stripe secret key' }}"
                                        autocomplete="new-password">
                                </div>
                                <div class="field-full">
                                    <label>Webhook secret</label>
                                    @if ($key->hasStripeWebhookSecret())
                                        <div class="crm-secret-hint">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Configured: <code>{{ $key->maskedStripeWebhookSecret() }}</code></span>
                                        </div>
                                    @endif
                                    <input type="password" name="stripe_webhook_secret" class="form-control"
                                        placeholder="{{ $key->hasStripeWebhookSecret() ? 'Leave blank to keep current webhook secret' : 'Enter Stripe webhook secret' }}"
                                        autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="crm-key-section">
                            <div class="crm-key-section-title crm-key-section-title--paypal">
                                <i class="fa fa-cc-paypal"></i> PayPal
                            </div>
                            <div class="crm-key-fields">
                                <div>
                                    <label>Client ID</label>
                                    <input type="text" name="paypal_client_id" class="form-control"
                                        value="{{ $key->paypal_client_id }}" autocomplete="off">
                                </div>
                                <div>
                                    <label>Secret</label>
                                    @if ($key->hasPaypalSecret())
                                        <div class="crm-secret-hint">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Configured: <code>{{ $key->maskedPaypalSecret() }}</code></span>
                                        </div>
                                    @endif
                                    <input type="password" name="paypal_secret" class="form-control"
                                        placeholder="{{ $key->hasPaypalSecret() ? 'Leave blank to keep current secret' : 'Enter PayPal secret' }}"
                                        autocomplete="new-password">
                                </div>
                                <div>
                                    <label>Webhook ID</label>
                                    <input type="text" name="paypal_webhook_id" class="form-control"
                                        value="{{ $key->paypal_webhook_id }}" autocomplete="off">
                                </div>
                                <div>
                                    <label>Base URL</label>
                                    <input type="text" name="paypal_base_url" class="form-control"
                                        value="{{ $key->paypal_base_url }}" placeholder="https://api-m.paypal.com"
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="crm-key-section mb-0">
                            <label class="form-label fw-semibold small text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" @selected($key->status === 'active')>Active</option>
                                <option value="inactive" @selected($key->status === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="crm-key-footer">
                        <button type="submit" class="btn btn-crm-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save changes
                        </button>
                    </div>
                </form>
            @endforeach
        </div>
    @endif

    {{-- Add keys modal --}}
    <div class="modal fade" id="addKeys" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-labelledby="addKeysLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addKeysLabel">Add payment account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.account-keys.post') }}">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CRM module</label>
                                <select name="module" class="form-select" required>
                                    <option value="" selected disabled>— Select module —</option>
                                    <option value="ppc">PPC</option>
                                    <option value="upwork">Upwork</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand / domain</label>
                                <select name="brand_id" class="form-select" required>
                                    <option value="">— Select brand —</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">
                                            {{ $brand->brand_name }} ({{ $brand->brand_url }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="crm-modal-section">
                            <div class="crm-modal-section-title">
                                <i class="fa fa-cc-stripe me-1"></i> Stripe credentials
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Secret key</label>
                                    <input type="password" name="stripe_secret_key" class="form-control"
                                        placeholder="sk_live_…" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Publishable key</label>
                                    <input type="text" name="stripe_publishable_key" class="form-control"
                                        placeholder="pk_live_…" autocomplete="off">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Webhook secret</label>
                                    <input type="password" name="stripe_webhook_secret" class="form-control"
                                        placeholder="whsec_…" autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="crm-modal-section mb-0">
                            <div class="crm-modal-section-title">
                                <i class="fa fa-cc-paypal me-1"></i> PayPal credentials
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" name="paypal_client_id" class="form-control" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secret</label>
                                    <input type="password" name="paypal_secret" class="form-control"
                                        autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Webhook ID</label>
                                    <input type="text" name="paypal_webhook_id" class="form-control" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Base URL</label>
                                    <input type="text" name="paypal_base_url" class="form-control"
                                        placeholder="https://api-m.paypal.com" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-crm-outline me-2" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-crm-primary">
                                <i class="bi bi-check2-lg me-1"></i> Save keys
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
