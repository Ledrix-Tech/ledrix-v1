@extends('central.layout.layout')

@section('title', 'Ledrix | Tenant Detail')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.company-profile.get') }}" class="sa-back">&larr; Back to tenants</a>
            <h1>{{ $tenant->name }}</h1>
            <p>{{ $tenant->slug }} · ID #{{ $tenant->id }}</p>
        </div>
    </div>

    <div class="sa-detail-grid">
        <div class="sa-card">
            <div class="sa-card-header">
                <h4>Profile</h4>
            </div>
            <div class="sa-card-body">
                <p><strong>Email:</strong> {{ $tenant->email }}</p>
                <p><strong>Phone:</strong> {{ $tenant->phone ?? '—' }}</p>
                <p><strong>Country:</strong> {{ $tenant->country ?? '—' }}</p>
                <p><strong>Website:</strong>
                    @if ($tenant->website)
                        <a href="{{ $tenant->website }}" target="_blank">{{ $tenant->website }}</a>
                    @else
                        —
                    @endif
                </p>
                <p><strong>Registered:</strong> {{ $tenant->created_at?->format('M d, Y H:i') ?? '—' }}</p>
                <p><strong>Email verified:</strong>
                    @if ($tenant->email_verified_at)
                        <span class="badge bg-success">{{ $tenant->email_verified_at->format('M d, Y') }}</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </p>
                <p class="mb-0"><strong>Status:</strong>
                    @php
                        $statusClass = match ($tenant->status) {
                            'active' => 'success',
                            'pending_email' => 'warning',
                            'suspended', 'inactive' => 'danger',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $tenant->status)) }}</span>
                </p>
                @if ($tenant->suspended_reason)
                    <p class="mt-2 mb-0 text-danger"><small>Reason: {{ $tenant->suspended_reason }}</small></p>
                @endif
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header">
                <h4>Plan & Trial</h4>
            </div>
            <div class="sa-card-body">
                <p><strong>Plan:</strong> {{ $tenant->plan?->name ?? '—' }}</p>
                @if ($tenant->activeMembership)
                    <p><strong>Membership status:</strong> {{ ucfirst($tenant->activeMembership->status) }}</p>
                    <p><strong>Billing cycle:</strong> {{ ucfirst($tenant->activeMembership->billing_cycle ?? '—') }}</p>
                    <p><strong>Period:</strong>
                        {{ $tenant->activeMembership->start_date?->format('M d, Y') ?? '—' }}
                        →
                        {{ $tenant->activeMembership->end_date?->format('M d, Y') ?? '—' }}
                    </p>
                @else
                    <p class="text-muted">No active membership yet.</p>
                @endif
                @if ($tenant->isOnTrial())
                    <p class="mb-0"><strong>Trial ends:</strong> {{ $tenant->trial_ends_at?->format('M d, Y') }} ({{ $tenant->trialDaysLeft() }} days left)</p>
                @endif
            </div>
        </div>
    </div>

    <div class="sa-card mb-4">
        <div class="sa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Plan limits</h4>
            <a href="{{ route('super-admin.tenant.features.get', $tenant->id) }}" class="btn btn-sm btn-outline-primary">
                Edit limits &amp; features
            </a>
        </div>
        <div class="sa-card-body p-0">
            <div class="table-responsive">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th>Limit</th>
                            <th class="text-center">Package</th>
                            <th class="text-center">Used</th>
                            <th class="text-center">Effective</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($limitSummary ?? [] as $limit)
                            <tr class="{{ $limit['at_limit'] ? 'table-warning' : '' }}">
                                <td data-label="Limit">{{ $limit['label'] }}</td>
                                <td data-label="Package" class="text-center">{{ \App\Support\TenantLimitCatalog::formatValue($limit['plan_default']) }}</td>
                                <td data-label="Used" class="text-center">{{ number_format($limit['used']) }}</td>
                                <td data-label="Effective" class="text-center">
                                    {{ \App\Support\TenantLimitCatalog::formatValue($limit['effective']) }}
                                    @if ($limit['at_limit'])
                                        <span class="badge bg-warning text-dark ms-1">Full</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="sa-card mb-4">
        <div class="sa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Feature access</h4>
            <a href="{{ route('super-admin.tenant.features.get', $tenant->id) }}" class="btn btn-sm btn-sa-primary">
                <i class="bi bi-toggles me-1"></i> Manage plan &amp; access
            </a>
        </div>
        <div class="sa-card-body">
            @if ($hasOverrides ?? false)
                <p class="small text-warning mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This tenant has custom feature overrides active.
                </p>
            @endif
            @if (($featureSummary ?? collect())->isEmpty())
                <p class="text-muted mb-0">No paid features enabled on the current plan.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($featureSummary as $feature)
                        <span class="badge bg-primary">{{ $feature['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($pendingPayments->isNotEmpty())
        <div class="sa-card border-warning">
            <div class="sa-card-header bg-warning bg-opacity-10">
                <h4 class="text-dark mb-0">Pending Payoneer Payments</h4>
            </div>
            <div class="sa-card-body p-0">
                <div class="sa-table-wrap">
                    <table class="table sa-table mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Tenant note</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingPayments as $payment)
                                <tr>
                                    <td data-label="Invoice">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                                    <td data-label="Reference"><code>{{ $payment->transaction_id }}</code></td>
                                    <td data-label="Amount">{{ strtoupper($payment->currency) }} {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td data-label="Note">{{ $payment->payload['tenant_note'] ?? ($payment->payload['tenant_marked_paid_at'] ?? '—') }}</td>
                                    <td data-label="Created">{{ $payment->created_at?->format('M d, Y H:i') }}</td>
                                    <td data-label="Action">
                                        <form method="POST" action="{{ route('super-admin.subscription-payments.confirm', $payment->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('Confirm Payoneer payment?')">Confirm</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="sa-card">
        <div class="sa-card-header">
            <h4>Invoice History</h4>
        </div>
        <div class="sa-card-body p-0">
            @include('central.pages.partials.invoice-table', ['invoices' => $invoices])
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body d-flex flex-wrap gap-2 align-items-center">
            <span class="me-2"><strong>Actions:</strong></span>
            @if ($tenant->stripe_customer_id && $tenant->stripe_payment_method_id)
                <form method="POST" action="{{ route('super-renew.send', $tenant->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary"
                        onclick="return confirm('Send renewal approval email to {{ $tenant->email }}?')">
                        Send renewal approval email
                    </button>
                </form>
            @endif
            @if ($tenant->status === 'active')
                <button type="button" class="btn btn-sm btn-danger sa-tenant-status-btn"
                    data-id="{{ $tenant->id }}" data-status="suspended">Suspend tenant</button>
            @else
                <button type="button" class="btn btn-sm btn-success sa-tenant-status-btn"
                    data-id="{{ $tenant->id }}" data-status="active">Activate tenant</button>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.SaTenantConfig = {
            statusUrl: @json(route('super-company.company-status')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('super-admin-assets/js/tenants.js') }}" defer></script>
@endpush
