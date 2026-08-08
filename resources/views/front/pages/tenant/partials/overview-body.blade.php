@php
    $dismissRoute = ($organizationPortal ?? 'tenant') === 'admin'
        ? 'admin.org.announcements.dismiss'
        : 'tenant.announcements.dismiss';
@endphp

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Organization overview</h4>
            <p class="text-muted mb-0 small">{{ $tenant->name }} · {{ $tenant->email }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ org_route('plan') }}" class="btn btn-outline-secondary btn-sm">Plan</a>
            <a href="{{ org_route('settings') }}" class="btn btn-outline-secondary btn-sm">Settings</a>
            <a href="{{ org_route('audit-logs') }}" class="btn btn-outline-secondary btn-sm">Audit log</a>
            <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
            <a href="{{ org_route('support.index') }}" class="btn btn-outline-secondary btn-sm">Support</a>
            <a href="{{ org_route('referrals') }}" class="btn btn-outline-secondary btn-sm">Referrals</a>
            @if (($organizationPortal ?? 'tenant') === 'admin')
                <a href="{{ route('admin.org.team') }}" class="btn btn-outline-secondary btn-sm">Team</a>
                @if ($tenantHasApiAccess ?? false)
                    <a href="{{ route('admin.org.api-tokens') }}" class="btn btn-outline-secondary btn-sm">API tokens</a>
                @endif
                <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Tenant portal</a>
                <a href="{{ route('admin.index.get') }}" class="btn btn-primary btn-sm">CRM dashboard</a>
            @endif
        </div>
    </div>

    @include('front.pages.tenant.partials.announcements', [
        'announcements' => $announcements ?? [],
        'dismissRoute'  => $dismissRoute,
    ])

    @if ($needsPayment)
        <div class="alert alert-warning">
            Your subscription has expired or payment is pending.
            <a href="{{ org_route('billing') }}" class="alert-link">Renew your subscription</a>
            to restore CRM access.
        </div>
    @elseif ($expiresSoon)
        <div class="alert alert-info">
            Subscription renews in {{ $daysUntilRenewal }} day(s)
            @if ($membership?->end_date)
                ({{ $membership->end_date->format('M d, Y') }})
            @endif.
            <a href="{{ org_route('billing') }}" class="alert-link">Renew early</a>
        </div>
    @elseif ($tenant->isOnTrial())
        <div class="alert alert-info">
            Free trial active — {{ $tenant->trialDaysLeft() }} day(s) left.
            <a href="{{ org_route('billing') }}" class="alert-link">View billing</a>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-dark text-white">Company</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Status:</strong>
                        <span class="badge bg-{{ $tenant->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>Country:</strong> {{ $tenant->country ?? 'N/A' }}</p>
                    <p class="mb-2"><strong>Website:</strong> {{ $tenant->website ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Joined:</strong> {{ $tenant->created_at?->format('M d, Y') ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-primary text-white">Subscription</div>
                <div class="card-body">
                    @if ($membership)
                        <p class="mb-2"><strong>Plan:</strong> {{ $plan?->name ?? 'N/A' }}</p>
                        <p class="mb-2"><strong>Cycle:</strong> {{ ucfirst($membership->billing_cycle) }}</p>
                        <p class="mb-2"><strong>Status:</strong>
                            <span class="badge bg-info text-dark">{{ ucfirst(str_replace('_', ' ', $membership->status)) }}</span>
                        </p>
                        <p class="mb-0"><strong>Period ends:</strong>
                            {{ $membership->end_date?->format('M d, Y') ?? '—' }}
                        </p>
                    @else
                        <p class="text-muted mb-0">No membership on file.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header">Quick links</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Manage billing</a>
                    <a href="{{ org_route('support.index') }}" class="btn btn-outline-secondary btn-sm">Platform support</a>
                    @if (($organizationPortal ?? 'tenant') === 'admin')
                        <a href="{{ route('admin.org.team') }}" class="btn btn-outline-secondary btn-sm">Manage team seats</a>
                    @endif
                    @if (! ($canUseCrm ?? true))
                        <a href="{{ org_route('billing') }}" class="btn btn-warning btn-sm">Subscribe / renew now</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($usage)
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header">Current usage</div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    @foreach ([
                        'Brands' => [$usage->total_brands, $limits['brands'] ?? null],
                        'Sellers' => [$usage->total_sellers, $limits['sellers'] ?? null],
                        'Admins' => [$usage->total_admins, $limits['admins'] ?? null],
                        'Clients' => [$usage->total_clients, $limits['clients'] ?? null],
                        'Orders' => [$usage->total_orders, $limits['orders'] ?? null],
                        'Leads (month)' => [$usage->leads_this_month, $limits['leads_monthly'] ?? null],
                    ] as $label => [$count, $max])
                        @php
                            $unlimited = $max === null || (int) $max === -1;
                        @endphp
                        <div class="col-6 col-md-2">
                            <div class="border rounded p-3">
                                <div class="fw-bold fs-4">
                                    {{ $count }}@if (! $unlimited)<span class="fs-6 text-muted fw-normal">/{{ (int) $max }}</span>@endif
                                </div>
                                <div class="small text-muted">{{ $label }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Anchor text for tests / quick scan --}}
    @if (($organizationPortal ?? 'tenant') === 'admin')
        <p class="visually-hidden">Plan usage · Team seats</p>
    @endif

    @if (($invoices ?? collect())->isNotEmpty())
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent invoices</span>
                <a href="{{ org_route('billing') }}" class="small">View billing</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Issued</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ org_route('billing.invoice.show', $invoice->id) }}">
                                            {{ $invoice->invoice_number ?? '#'.$invoice->id }}
                                        </a>
                                    </td>
                                    <td>{{ ucfirst($invoice->status) }}</td>
                                    <td>{{ $invoice->currency ?? '' }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
