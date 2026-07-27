@extends('sellers.layout.layout')

@section('title', 'Seller | Pm Leads')

@section('sellers-content')


    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Assigned Leads</h1>
                    </div>
                    <div class="d-flex">
                        <div class="examplesearch-form mx-3">
                            <form method="GET" class="flex gap-2">
                                <div class="d-flex">
                                    <select name="status" class="form-control">
                                        <option class="text-white" value="">All statuses</option>
                                        @foreach (['new', 'contacted', 'qualified', 'client', 'disqualified'] as $s)
                                            <option value="{{ $s }}" @selected(request('status') === $s)>
                                                {{ ucfirst($s) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success" type="submit">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5">
            <div class="col-lg-12">
                <table class="table table-striped" id="invoiceTable">
                    <thead class=" text-white text-center" style="background: #000;">
                        <th>#id</th>
                        <th>Seller</th>
                        <th>Client</th>
                        <th>Lead Domain</th>
                        <th>Payment</th>
                        <th>Assigned Status</th>
                        <th>Actions</th>
                    </thead>
                    <tbody class="border text-center">
                        @if ($leads->isEmpty())
                            <tr>
                                <td colspan="6">
                                    <div class="alert alert-info m-0">
                                        <h6>You don't have any leads assigned yet !!!</h6>
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
                                    <td>{{ $leads->firstItem() + $i }}</td>
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
                                        <div class="fw-semibold">{{ $lead->name ?? '—' }}</div>
                                        <div class="text-muted small ">
                                            <a
                                                href="mailto:{{ $lead->email ?? '—' }}">{{ Str::mask($lead->email, '*', 3) ?? '—' }}</a>
                                            <br>
                                            <a
                                                href="tel:{{ $lead->phone ?? '—' }}">{{ Str::mask($lead->phone, '*', 3) ?? '—' }}</a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $lead->domain_url }}
                                        </div>
                                        <span class="small text-muted">Servcie: {{ $lead->meta['service'] }}</span>
                                    </td>
                                    @php
                                        $authSeller = auth('seller')->user();
                                        $isAdmin = auth('admin')->check();
                                        $role = $authSeller?->role ?? $authSeller?->is_seller; // 'front_seller' | 'project_manager' | null
                                        $isFront = $role === 'front_seller';
                                        $orderId = $lead->latest_order_id;
                                        $due = (int) ($lead->latest_order_balance_due ?? 0);
                                        $currency = $lead->latest_order_currency ?? 'USD';
                                        $hasOrder = !empty($orderId);
                                        $isPaidAll = $hasOrder && $due <= 0;
                                        // Can change lead status if Admin OR Seller
                                        $canChangeStatus = $isAdmin || $isSeller;
                                    @endphp
                                    <td>
                                        @if (!$hasOrder)
                                            <span class="badge badge-secondary"><i class="fa fa-info-circle"></i> No order
                                                yet</span>
                                        @elseif ($isPaidAll)
                                            <span class="badge badge-success"><i class="fa fa-check-circle"></i> Paid in
                                                Full</span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fa fa-clock"></i> Due: {{ number_format($due / 100, 2) }}
                                                {{ $currency }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($lead->assignments->where('assigned_to', auth('seller')->id())->isNotEmpty())
                                            @php
                                                $assignment = $lead->assignments
                                                    ->where('assigned_to', auth('seller')->id())
                                                    ->first();
                                            @endphp
                                            <form method="POST" action="{{ route('seller.assignment.update-status') }}">
                                                @csrf
                                                <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                                                <select name="status" class="form-control" onchange="this.form.submit()">
                                                    <option value="pending"
                                                        {{ $assignment->status == 'pending' ? 'selected' : '' }}>Pending
                                                    </option>
                                                    <option value="assigned"
                                                        {{ $assignment->status == 'assigned' ? 'selected' : '' }}>Assigned
                                                    </option>
                                                    <option value="in_progress"
                                                        {{ $assignment->status == 'in_progress' ? 'selected' : '' }}>In
                                                        Progress</option>
                                                    <option value="on_hold"
                                                        {{ $assignment->status == 'on_hold' ? 'selected' : '' }}>On Hold
                                                    </option>
                                                    <option value="completed"
                                                        {{ $assignment->status == 'completed' ? 'selected' : '' }}>
                                                        Completed</option>
                                                    <option value="refund_requested"
                                                        {{ $assignment->status == 'refund_requested' ? 'selected' : '' }}>
                                                        Refund Requested</option>
                                                    <option value="chargeback"
                                                        {{ $assignment->status == 'chargeback' ? 'selected' : '' }}>
                                                        Chargeback</option>
                                                    <option value="rejected_by_client"
                                                        {{ $assignment->status == 'rejected_by_client' ? 'selected' : '' }}>
                                                        Rejected by Client</option>
                                                    <option value="cancelled"
                                                        {{ $assignment->status == 'cancelled' ? 'selected' : '' }}>
                                                        Cancelled</option>
                                                </select>
                                            </form>
                                        @else
                                            <span class="badge bg-secondary">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('seller.lead-details.get', $lead->id) }}"
                                            style="text-decoration: none;">
                                            <button type="button" class="badge badge-light">
                                                <i class="fa fa-eye text-info"></i>
                                            </button>
                                        </a>
                                        @if (isAdmin())
                                            <form method="POST" action="{{ route('seller.leads.delete', $lead->id) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete this lead?')">
                                                @csrf
                                                <button type="submit" class="badge badge-primary">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>

                </table>
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $leads->links() }}
                        <div hidden>
                            @if ($leads->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $leads->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $leads->url($leads->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $leads->currentPage(); $i <= $leads->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $leads->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $leads->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $leads->currentPage() == $leads->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $leads->url($leads->currentPage() + 1) }}">Next</a>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Convert table to CSV (with skipped columns)
        function tableToCSV(skipCols = []) {
            let csv = [];
            let rows = document.querySelectorAll("#invoiceTable tr");

            for (let row of rows) {
                let cols = row.querySelectorAll("td, th");
                let rowData = [];
                cols.forEach((col, index) => {
                    if (!skipCols.includes(index)) { // Skip unwanted column indexes
                        rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
                    }
                });
                csv.push(rowData.join(","));
            }
            return csv.join("\n");
        }

        // Download CSV file
        document.getElementById("download-csv").addEventListener("click", function() {
            // Example: skip column 0 and last column
            let csv = tableToCSV([0, 6]);
            let blob = new Blob([csv], {
                type: "text/csv"
            });
            let link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);
            link.download = "client-leads.csv";
            link.click();
        });
    </script>



@endsection
