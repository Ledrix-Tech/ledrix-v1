@extends('central.layout.layout')

@section('title', 'Ledrix | Dashboard')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Platform overview and recent activity</p>
        </div>
    </div>

    <div class="sa-stats">
        <a href="{{ route('super-admin.company-profile.get') }}" class="sa-stat">
            <div class="sa-stat-icon"><i class="bi bi-buildings"></i></div>
            <div class="sa-stat-label">Tenants</div>
            <div class="sa-stat-value">{{ $totalTenants }}</div>
            <div class="sa-stat-meta">{{ $activeTenants }} active · {{ $pendingEmailTenants }} unverified</div>
        </a>
        <div class="sa-stat">
            <div class="sa-stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="sa-stat-label">New This Month</div>
            <div class="sa-stat-value">{{ $newTenantsThisMonth }}</div>
        </div>
        <a href="{{ route('super-admin.company-profile.get') }}" class="sa-stat">
            <div class="sa-stat-icon"><i class="bi bi-envelope-exclamation"></i></div>
            <div class="sa-stat-label">Awaiting Verification</div>
            <div class="sa-stat-value">{{ $pendingEmailTenants }}</div>
        </a>
        <a href="{{ route('super-admin.contact-queries.get') }}" class="sa-stat">
            <div class="sa-stat-icon"><i class="bi bi-chat-left-text"></i></div>
            <div class="sa-stat-label">Contact Queries</div>
            <div class="sa-stat-value">{{ $pendingContactQueries }}</div>
        </a>
        <a href="{{ route('super-admin.subscription-payments.get') }}" class="sa-stat">
            <div class="sa-stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="sa-stat-label">Pending Manual Payments</div>
            <div class="sa-stat-value">{{ $pendingSubscriptionPayments }}</div>
            <div class="sa-stat-meta">Meezan + Payoneer</div>
        </a>
    </div>

    <div class="sa-card">
        <div class="sa-card-header">
            <h4>Recent Tenant Registrations</h4>
            <a href="{{ route('super-admin.company-profile.get') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTenants as $tenant)
                            <tr>
                                <td data-label="#">{{ $tenant->id }}</td>
                                <td data-label="Tenant">
                                    <strong>{{ $tenant->name }}</strong><br>
                                    <small class="text-muted">{{ $tenant->slug }}</small>
                                </td>
                                <td data-label="Email">{{ $tenant->email }}</td>
                                <td data-label="Plan">{{ $tenant->plan?->name ?? '—' }}</td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($tenant->status) {
                                            'active' => 'success',
                                            'pending_email' => 'warning',
                                            'suspended' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $tenant->status)) }}</span>
                                </td>
                                <td data-label="Verified">
                                    @if ($tenant->email_verified_at)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td data-label="Registered">{{ $tenant->created_at?->format('M d, Y') }}</td>
                                <td data-label="Action">
                                    <a href="{{ route('super-admin.tenant.show', $tenant->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No tenants registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-header">
            <h4>Recent Subscription Invoices</h4>
            <a href="{{ route('super-admin.subscription-payments.get') }}" class="btn btn-sm btn-outline-primary">Pending payments</a>
        </div>
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Tenant</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Due</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $invoice)
                            <tr>
                                <td data-label="Invoice"><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td data-label="Tenant">
                                    @if ($invoice->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $invoice->tenant->id) }}">{{ $invoice->tenant->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Plan">{{ $invoice->plan_name ?? '—' }}</td>
                                <td data-label="Amount">{{ strtoupper($invoice->currency) }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td data-label="Status">
                                    @php
                                        $invoiceStatusClass = match ($invoice->status) {
                                            'paid' => 'success',
                                            'issued' => 'warning',
                                            'void' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $invoiceStatusClass }}">{{ ucfirst($invoice->status) }}</span>
                                </td>
                                <td data-label="Issued">{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</td>
                                <td data-label="Due">{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</td>
                                <td data-label="Reference">
                                    @if ($invoice->payment)
                                        <code class="small">{{ $invoice->payment->transaction_id }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No subscription invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
