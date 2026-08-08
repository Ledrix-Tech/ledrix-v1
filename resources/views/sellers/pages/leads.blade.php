@extends('sellers.layout.layout')

@section('title', 'Seller | Leads')

@section('sellers-content')
    @php use Illuminate\Support\Facades\Gate; @endphp
    <div class="crm-page-header">
        <div>
            <h1>Leads</h1>
            <p>Track and manage your sales pipeline</p>
        </div>
        <div class="crm-page-actions">
            <form method="GET" class="crm-filter-bar mb-0">
                @php
                    $statuses = [
                        'new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified',
                        'proposal_sent' => 'Proposal Sent', 'first_paid' => 'First Paid',
                        'in_progress' => 'In Progress', 'completed' => 'Completed',
                        'renewal_due' => 'Renewal Due', 'on_hold' => 'On Hold',
                        'disqualified' => 'Disqualified', 'cancelled' => 'Cancelled',
                    ];
                    $selectedStatus = request('status');
                @endphp
                <select name="status" class="form-select crm-filter-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped" id="invoiceTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Seller</th>
                            <th>Client</th>
                            <th>Domain</th>
                            <th>Payment</th>
                            <th>Assigned</th>
                            <th>Assign Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($leads->isEmpty())
                            <tr>
                                <td colspan="9">
                                    <div class="crm-empty">
                                        <i class="bi bi-funnel d-block"></i>
                                        No leads assigned yet.
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach ($leads as $i => $lead)
                                @php
                                    $seller = auth('seller')->user();
                                    $isSeller = auth('seller')->check() && !auth('admin')->check();
                                    $authSeller = auth('seller')->user();
                                    $isAdmin = auth('admin')->check();
                                    $canGenerateFirst = false;
                                    if ($isAdmin) {
                                        $canGenerateFirst = true;
                                    } elseif ($authSeller && Gate::forUser($authSeller)->allows('createPaymentLink', $lead)) {
                                        $canGenerateFirst = true;
                                    }
                                    $canGenerateFirst = $canGenerateFirst && ($tenantHasPayments ?? tenantHasPayments());
                                    $canFinish = $isAdmin || ($authSeller && Gate::forUser($authSeller)->allows('finish', $lead));
                                    $orderId = $lead->latest_order_id;
                                    $due = (int) ($lead->latest_order_balance_due ?? 0);
                                    $currency = $lead->latest_order_currency ?? 'USD';
                                    $hasOrder = !empty($orderId);
                                    $isPaidAll = $hasOrder && $due <= 0;
                                    $canChangeStatus = $isAdmin || $isSeller;
                                @endphp
                                <tr>
                                    <td>{{ $leads->firstItem() + $i }}</td>
                                    <td data-label="Seller">
                                        @if ($isSeller && $seller->id == $lead->seller->id)
                                            <div class="text-muted fw-semibold">It's yours</div>
                                        @else
                                            <div class="fw-semibold">{{ $lead->seller->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                {{ $lead->seller->email ?? ($lead->seller->sudo_name ?? '—') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="blurred" data-label="Client">
                                        {{ $lead->name }}
                                        <a href="mailto:{{ $lead->email }}">
                                            <div class="text-muted small">{{ $lead->email ?? '—' }}</div>
                                        </a>
                                        <a href="tel:{{ $lead->phone }}">
                                            <span class="text-muted small">{{ $lead->phone ?? '—' }}</span>
                                        </a>
                                    </td>
                                    <td data-label="Domain">
                                        <div>{{ $lead->domain_url }}</div>
                                        <span class="small text-muted">Service: {{ $lead->meta['service'] ?? '—' }}</span>
                                    </td>
                                    <td data-label="Payment">
                                        <div class="crm-payment-cell">
                                            @if (!$hasOrder)
                                                <span class="crm-status crm-status-neutral">
                                                    <i class="bi bi-receipt"></i> No order yet
                                                </span>
                                            @elseif ($isPaidAll)
                                                <span class="crm-status crm-status-success">
                                                    <i class="bi bi-check-circle-fill"></i> Paid in full
                                                </span>
                                            @else
                                                <span class="crm-status crm-status-warning">
                                                    <i class="bi bi-clock"></i>
                                                    Due: {{ number_format($due / 100, 2) }} {{ $currency }}
                                                </span>
                                            @endif
                                            @if ($canGenerateFirst)
                                                @if (!$hasOrder)
                                                    <a href="{{ route('generate-link-form', ['brand' => $lead->brand_id, 'lead' => $lead->id]) }}"
                                                        class="btn btn-sm btn-crm-primary crm-generate-link">
                                                        <i class="bi bi-link-45deg"></i> Generate link
                                                    </a>
                                                @elseif (!$isPaidAll)
                                                    <span class="crm-payment-hint">Create next link from Orders</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Assigned">
                                        <form method="POST" action="{{ route('seller.lead-assign.post') }}">
                                            @csrf
                                            <input type="hidden" name="lead_id" value="{{ $lead['id'] }}">
                                            <select name="seller_id" class="form-select form-select-sm"
                                                onchange="this.form.submit()">
                                                <option value="">-- select --</option>
                                                @foreach ($pmSellers as $pmSeller)
                                                    <option value="{{ $pmSeller->id }}"
                                                        @if ($lead->assignments->isNotEmpty() && $lead->assignments->first()->assigned_to == $pmSeller->id) selected @endif>
                                                        {{ $pmSeller->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td data-label="Assign Status">
                                        @if ($lead->latestAssignment)
                                            @php
                                                $assignClass = match ($lead->latestAssignment->status) {
                                                    'pending' => 'crm-status-warning',
                                                    'assigned' => 'crm-status-info',
                                                    default => 'crm-status-success',
                                                };
                                            @endphp
                                            <span class="crm-status {{ $assignClass }}">
                                                {{ ucfirst($lead->latestAssignment->status) }}
                                            </span>
                                        @else
                                            <span class="crm-status crm-status-neutral">Not assigned</span>
                                        @endif
                                    </td>
                                    <td data-label="Status">
                                        @if ($canChangeStatus)
                                            <form method="POST" action="{{ route('lead.update-status') }}">
                                                @csrf
                                                <input type="hidden" name="lead_id" value="{{ $lead['id'] }}">
                                                <select name="status" class="form-select form-select-sm"
                                                    onchange="this.form.submit()">
                                                    @foreach ($statuses as $value => $label)
                                                        <option value="{{ $value }}" @selected($lead->status === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <span class="crm-status crm-status-neutral">
                                                <i class="bi bi-lock-fill"></i> {{ $lead->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td data-label="Actions">
                                        <div class="crm-action-group">
                                            <a href="{{ route('seller.lead-details.get', $lead->id) }}"
                                                class="crm-icon-btn info" title="View details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if ($canFinish && $isPaidAll)
                                                <form method="POST" action="{{ route('seller.lead.finish', $lead) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('{{ $lead->is_finish ? 'Reopen this lead?' : 'Mark this lead as finished?' }}')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="crm-icon-btn {{ $lead->is_finish ? 'warning' : 'success' }}"
                                                        title="{{ $lead->is_finish ? 'Reopen lead' : 'Mark finished' }}">
                                                        <i class="fa {{ $lead->is_finish ? 'fa-undo' : 'fa-check' }}"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if (isAdmin())
                                                <form method="POST" action="{{ route('seller.leads.delete', $lead->id) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete this lead?')">
                                                    @csrf
                                                    <button type="submit" class="crm-icon-btn danger" title="Delete lead">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($leads->hasPages())
                <div class="crm-pagination">{{ $leads->links() }}</div>
            @endif
        </div>
    </div>
@endsection
