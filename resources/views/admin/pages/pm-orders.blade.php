@extends('admin.layout.layout')

@section('title', 'Admin | Orders')

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Orders</h1>
            <p>Project manager order overview</p>
        </div>
        <div class="crm-page-actions">
            @php
                $user = Auth::guard('admin')->user();
                $meta = json_decode($user->meta ?? '{}', true);
                $isAdmin = $user !== null;
                $isSuperAdmin = $isAdmin && is_array($meta) && isset($meta['role']) && $meta['role'] === 'white_wolf';
            @endphp
            @if ($orders->count() >= 300 && ($isAdmin || $isSuperAdmin))
                <button class="btn btn-sm btn-crm-teal" type="button" id="download-csv">
                    <i class="fa fa-file-excel-o me-1"></i> Export CSV
                </button>
            @endif
            <form method="GET" class="crm-search-bar mb-0">
                <input type="text" placeholder="Search orders..." value="{{ request('q') }}"
                    name="q" class="form-control">
                <button type="submit" class="btn btn-crm-teal"><i class="fa fa-search"></i></button>
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
                            <th>Brand</th>
                            <th>Service</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment</th>
                            <th>Paid at</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $i => $order)
                            @php
                                $seller = auth('seller')->user();
                                $isSeller = auth('seller')->check() && !auth('admin')->check();
                            @endphp
                            <tr>
                                <td data-label="#">{{ $orders->firstItem() + $i }}</td>
                                <td data-label="Seller">
                                    @if ($isSeller && $seller->id == $order->seller->id)
                                        <div class="text-muted fw-semibold">It's yours</div>
                                    @else
                                        <div class="fw-semibold">{{ $order->seller->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $order->seller->email ?? ($order->seller->sudo_name ?? '—') }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Brand">{{ $order->brand->brand_name ?? '—' }}</td>
                                <td data-label="Service">{{ $order->service_name ?? '—' }}</td>
                                <td data-label="Client">
                                    <div class="fw-semibold">{{ $order->client->name ?? '—' }}</div>
                                    <div class="text-muted small">
                                        {{ $order->client->email ?? ($order->buyer_email ?? '—') }}
                                    </div>
                                </td>
                                <td data-label="Total">
                                    {{ number_format(($order->unit_amount ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}
                                </td>
                                <td data-label="Paid">
                                    {{ number_format(($order->amount_paid ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}
                                </td>
                                <td data-label="Due">
                                    {{ number_format(($order->balance_due ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}
                                </td>
                                <td data-label="Payment">
                                    @include('admin.includes.order-payment-cell', ['order' => $order])
                                </td>
                                <td data-label="Paid at">
                                    {{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}
                                </td>
                                <td data-label="Actions" class="text-end">
                                    <div class="crm-order-actions">
                                        @if ($tenantHasDualInvoicing ?? false)
                                            <a href="{{ route('order.generate-invoice', $order) }}"
                                                class="btn btn-sm btn-crm-outline" title="Invoice">
                                                <i class="fa fa-file-text-o"></i> Invoice
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="crm-empty">
                                        <i class="bi bi-cart-x d-block"></i>
                                        No orders found.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="crm-pagination">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.getElementById('download-csv')?.addEventListener('click', function() {
            function tableToCSV(skipCols = []) {
                let csv = [];
                document.querySelectorAll('#invoiceTable tr').forEach(function(row) {
                    let rowData = [];
                    row.querySelectorAll('td, th').forEach(function(col, index) {
                        if (!skipCols.includes(index)) {
                            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
                        }
                    });
                    csv.push(rowData.join(','));
                });
                return csv.join('\n');
            }
            let blob = new Blob([tableToCSV([0, 8, 10])], { type: 'text/csv' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'orders-data.csv';
            link.click();
        });
    </script>
@endpush
