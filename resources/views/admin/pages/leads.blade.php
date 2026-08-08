@extends('admin.layout.layout')

@section('title', 'Admin | Leads')

@section('admin-content')


    <div class="crm-page-header">
        <div>
            <h1>Leads</h1>
            <p>Track and manage incoming sales leads</p>
        </div>
        <div class="crm-page-actions">
            @if (isAdmin())
                <a href="{{ route('export.csv', ['table' => 'leads', 'columns' => 'id,name,email,phone,domain,message,status,prediction,meta']) }}"
                    class="btn btn-sm btn-crm-teal">
                    <i class="fa fa-file-excel-o me-1"></i> Export CSV
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal"
                    data-target="#deleteLeads">
                    <i class="fa fa-trash me-1"></i> Delete Leads
                </button>
            @endif
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
                            @if ($showLeadPrediction ?? false)
                                <th>Prediction</th>
                            @endif
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
                                <td colspan="{{ ($showLeadPrediction ?? false) ? 10 : 9 }}">
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
                                        $ownsLead =
                                            $isSeller &&
                                            $seller->id === $lead->seller_id &&
                                            $seller->brand_id === $lead->brand_id;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $leads->firstItem() + $i }}
                                        </td>
                                        <td>
                                            @if ($isSeller && $seller->id == $lead->seller->id)
                                                <div class="text-muted fw-semibold">It's your's</div>
                                            @else
                                                <div class="fw-semibold">{{ $lead->seller->name ?? '—' }}</div>
                                                <div class="text-muted small">
                                                    {{ $lead->seller->email ?? ($lead->seller->sudo_name ?? '—') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="blurred">
                                            {{ $lead->name }}
                                            <a href="mailto:{{ $lead->email }}">
                                                <div class="text-muted small">
                                                    {{ $lead->email ?? ($lead->email ?? '—') }}
                                                </div>
                                            </a>
                                            <a href="tel:{{ $lead->phone }}">
                                                <span class="text-muted small">
                                                    {{ $lead->phone ?? ($lead->phone ?? '—') }}
                                                </span>
                                            </a>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $lead->domain_url }}
                                            </div>
                                            <span class="small text-muted">Servcie:
                                                {{ $lead->meta['service'] ?? '—' }}</span>
                                        </td>
                                        @if ($showLeadPrediction ?? false)
                                            <td data-label="Prediction">
                                                @include('admin.includes.lead-prediction', [
                                                    'prediction' => $lead->prediction,
                                                    'compact' => true,
                                                ])
                                            </td>
                                        @endif
                                        @php
                                            $authSeller = auth('seller')->user();
                                            $isAdmin = auth('admin')->check();
                                            $role = $authSeller?->role ?? $authSeller?->is_seller; // 'front_seller' | 'project_manager' | null
                                            $isFront = $role === 'front_seller';
                                            $canGenerateFirst = false;
                                            if ($isAdmin && ($tenantHasPayments ?? tenantHasPayments())) {
                                                $canGenerateFirst = true;
                                            } elseif (
                                                $isFront &&
                                                $authSeller &&
                                                (int) $authSeller->brand_id === (int) $lead->brand_id &&
                                                ($tenantHasPayments ?? tenantHasPayments())
                                            ) {
                                                // Only front_seller in same brand can generate
                                                $canGenerateFirst = true;
                                            }
                                            $orderId = $lead->latest_order_id;
                                            $due = (int) ($lead->latest_order_balance_due ?? 0);
                                            $currency = $lead->latest_order_currency ?? 'USD';
                                            $hasOrder = !empty($orderId);
                                            $isPaidAll = $hasOrder && $due <= 0;

                                            // ✅ Can change lead status if Admin OR Seller
                                            $canChangeStatus = $isAdmin || $isSeller;
                                        @endphp
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
                                        <td>
                                            <form method="POST" action="{{ route('lead-assign.post') }}">
                                                @csrf
                                                <input type="hidden" name="lead_id" value="{{ $lead['id'] }}">
                                                <select name="seller_id" class="form-select form-select-sm form-control"
                                                    onchange="this.form.submit()">
                                                    <option value="">-- select --</option>
                                                    @foreach ($pmSellers->where('brand_id', $lead->brand_id) as $pmSeller)
                                                        <option value="{{ $pmSeller->id }}"
                                                            @selected($lead->latestAssignment?->assigned_to == $pmSeller->id)>
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

                                        <td>
                                            @if ($canChangeStatus)
                                                <form method="POST" action="{{ route('lead.update-status') }}">
                                                    @csrf
                                                    <input type="hidden" name="lead_id" value="{{ $lead['id'] }}">

                                                    <select name="status" class="form-control"
                                                        onchange="this.form.submit()">
                                                        <option value="new"
                                                            {{ $lead->status == 'new' ? 'selected' : '' }}>New</option>
                                                        <option value="contacted"
                                                            {{ $lead->status == 'contacted' ? 'selected' : '' }}>Contacted
                                                        </option>
                                                        <option value="qualified"
                                                            {{ $lead->status == 'qualified' ? 'selected' : '' }}>Qualified
                                                        </option>
                                                        <option value="proposal_sent"
                                                            {{ $lead->status == 'proposal_sent' ? 'selected' : '' }}>
                                                            Proposal Sent</option>
                                                        <option value="first_paid"
                                                            {{ $lead->status == 'first_paid' ? 'selected' : '' }}>First
                                                            Paid</option>
                                                        <option value="in_progress"
                                                            {{ $lead->status == 'in_progress' ? 'selected' : '' }}>In
                                                            Progress</option>
                                                        <option value="completed"
                                                            {{ $lead->status == 'completed' ? 'selected' : '' }}>Completed
                                                        </option>
                                                        <option value="renewal_due"
                                                            {{ $lead->status == 'renewal_due' ? 'selected' : '' }}>Renewal
                                                            Due</option>
                                                        <option value="on_hold"
                                                            {{ $lead->status == 'on_hold' ? 'selected' : '' }}>On Hold
                                                        </option>
                                                        <option value="disqualified"
                                                            {{ $lead->status == 'disqualified' ? 'selected' : '' }}>
                                                            Disqualified</option>
                                                        <option value="cancelled"
                                                            {{ $lead->status == 'cancelled' ? 'selected' : '' }}>Cancelled
                                                        </option>
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
                                                <a href="{{ route('admin.lead-details.get', $lead->id) }}"
                                                    class="crm-icon-btn info" title="View details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if (isAdmin())
                                                    <form method="POST" action="{{ route('admin.leads.delete', $lead->id) }}"
                                                        class="d-inline"
                                                        onsubmit="return confirm('Delete this lead?')">
                                                        @csrf
                                                        <button type="submit" class="crm-icon-btn danger border-0"
                                                            title="Delete lead">
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

    <!-- Modal -->
    <div class="modal fade" id="deleteLeads" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Lead Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.leads.delete') }}" class="leadform" id="form1">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <select name="status" class="form-control">
                                        <option value="new">New</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="qualified">Qualified</option>
                                        <option value="proposal_sent">Proposal Sent</option>
                                        <option value="first_paid">First Paid</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="renewal_due">Renewal Due</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="disqualified">Disqualified</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="d-flex align-items-center justify-content-center text-center m-auto">
                                    <button class="btn btn-success text-white">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("download-csv")?.addEventListener("click", function() {
            function tableToCSV(skipCols = []) {
                let csv = [];
                let rows = document.querySelectorAll("#invoiceTable tr");
                for (let row of rows) {
                    let cols = row.querySelectorAll("td, th");
                    let rowData = [];
                    cols.forEach((col, index) => {
                        if (!skipCols.includes(index)) {
                            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
                        }
                    });
                    csv.push(rowData.join(","));
                }
                return csv.join("\n");
            }
            let csv = tableToCSV([0, 6]);
            let blob = new Blob([csv], { type: "text/csv" });
            let link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);
            link.download = "client-leads.csv";
            link.click();
        });
    </script>



@endsection
