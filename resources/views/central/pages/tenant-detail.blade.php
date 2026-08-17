@extends('central.layout.layout')

@section('title', 'Ledrix | Tenant Detail')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp

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
                <p><strong>Billing region:</strong>
                    {{ $billingRegionLabel ?? '—' }}
                    @if (! empty($billingCurrency))
                        <span class="badge bg-light text-dark border">{{ $billingCurrency }}</span>
                    @endif
                </p>
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

    @php
        $registeredFrom = data_get($tenant->meta, 'registered_from');
        $landingPath = data_get($tenant->meta, 'landing_path');
        $attribution = data_get($tenant->meta, 'attribution', []);
        $conversionSource = $tenant->activeMembership?->conversion_source
            ?? optional($tenant->memberships->sortByDesc(fn ($m) => (string) $m->start_date)->first())->conversion_source;
    @endphp
    <div class="sa-card mb-4">
        <div class="sa-card-header">
            <h4 class="mb-0">Marketing attribution</h4>
        </div>
        <div class="sa-card-body">
            @if ($conversionSource)
                <p class="mb-2"><strong>Conversion:</strong> <code>{{ $conversionSource }}</code></p>
            @endif
            @include('central.partials.attribution-block', [
                'source' => $registeredFrom,
                'landingPath' => $landingPath,
                'attribution' => is_array($attribution) ? $attribution : [],
            ])
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
                            <th style="min-width: 120px;">Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($limitSummary ?? [] as $limit)
                            @php
                                $unlimited = ($limit['unlimited'] ?? false) || (int) ($limit['effective'] ?? 0) === -1;
                                $percent = (float) ($limit['percent'] ?? 0);
                            @endphp
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
                                <td data-label="Usage">
                                    @if ($unlimited)
                                        <span class="text-muted small">Unlimited</span>
                                    @else
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar {{ $limit['at_limit'] ? 'bg-warning' : 'bg-primary' }}"
                                                role="progressbar" style="width: {{ $percent }}%;"
                                                aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted">{{ $percent }}%</small>
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

    <div class="sa-card mb-4">
        <div class="sa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Recent activity</h4>
            <a href="{{ route('super-admin.audit-logs.get', ['tenant_id' => $tenant->id]) }}"
                class="btn btn-sm btn-outline-primary">View all logs</a>
        </div>
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>Actor</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAuditLogs ?? [] as $log)
                            <tr>
                                <td data-label="When">{{ $log->created_at?->format('M d, H:i') ?? '—' }}</td>
                                <td data-label="Action"><code>{{ $log->action }}</code></td>
                                <td data-label="Actor">{{ $log->actor_name ?? $log->actor_type }}</td>
                                <td data-label="Description">{{ $log->description ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No audit activity for this tenant yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($pendingPayments->isNotEmpty())
        <div class="sa-card border-warning mb-4">
            <div class="sa-card-header bg-warning bg-opacity-10">
                <h4 class="text-dark mb-0">Pending Manual Payments</h4>
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
                                    <td data-label="Invoice">
                                        {{ $payment->invoice?->invoice_number ?? '—' }}
                                        <br><small class="text-muted">{{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}</small>
                                    </td>
                                    <td data-label="Reference"><code>{{ $payment->transaction_id }}</code></td>
                                    <td data-label="Amount">{{ strtoupper($payment->currency) }} {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td data-label="Note">{{ $payment->payload['tenant_note'] ?? ($payment->payload['customer_reported_note'] ?? '—') }}</td>
                                    <td data-label="Created">{{ $payment->created_at?->format('M d, Y H:i') }}</td>
                                    <td data-label="Action">
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('super-admin.subscription-payments.confirm', $payment->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Confirm this manual payment and activate subscription?')">Confirm</button>
                                            </form>
                                        @else
                                            <span class="text-muted">View only</span>
                                        @endif
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
        <div class="sa-card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">API Tokens</h4>
        </div>
        <div class="sa-card-body">
            @if (session('new_api_token'))
                <div class="alert alert-warning">
                    <strong>Copy this token now — it will not be shown again.</strong>
                    <input type="text" class="form-control mt-2 font-monospace" readonly value="{{ session('new_api_token') }}" onclick="this.select()">
                </div>
            @endif
            @if ($canManage)
                <form method="POST" action="{{ route('super-admin.tenant.api-tokens.store', $tenant->id) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Production API">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Abilities</label>
                        <input type="text" name="abilities" class="form-control" placeholder="* or leads:classify,leads:read">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Expires</label>
                        <input type="date" name="expires_at" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sa-primary w-100">Create</button>
                    </div>
                </form>
            @endif
        </div>
        <div class="sa-card-body p-0 border-top">
            <div class="sa-table-wrap">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Last used</th>
                            <th>Expires</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apiTokens as $token)
                            <tr>
                                <td data-label="Name">
                                    <strong>{{ $token->name }}</strong>
                                    @if (is_array($token->abilities) && count($token->abilities))
                                        <br><small class="text-muted">{{ implode(', ', $token->abilities) }}</small>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $token->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($token->status) }}</span>
                                </td>
                                <td data-label="Last used">
                                    {{ $token->last_used_at?->diffForHumans() ?? 'Never' }}
                                    @if ($token->last_used_ip)
                                        <br><small class="text-muted">{{ $token->last_used_ip }}</small>
                                    @endif
                                </td>
                                <td data-label="Expires">{{ $token->expires_at?->format('M d, Y') ?? '—' }}</td>
                                <td data-label="Action">
                                    @if ($canManage && $token->status === 'active')
                                        <form method="POST" action="{{ route('super-admin.tenant.api-tokens.revoke', [$tenant->id, $token->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Revoke this API token?')">Revoke</button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No API tokens for this tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($canManage)
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
    @endif

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
