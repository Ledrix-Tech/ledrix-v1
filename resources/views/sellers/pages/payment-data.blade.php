@extends('sellers.layout.layout')

@section('title', 'Seller | Payments')

@section('sellers-content')
    <div class="crm-page-header">
        <div>
            <h1>Payments</h1>
            <p>Payment history and order settlement</p>
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
                            <th>Paid at</th>
                            <th class="text-end">Type</th>
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
                                <td data-label="Paid at">
                                    {{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}
                                </td>
                                <td data-label="Type" class="text-end">
                                    @if ($order->order_type === 'renewal')
                                        <span class="crm-status crm-status-danger">
                                            <i class="bi bi-arrow-repeat"></i> Renewed
                                        </span>
                                    @else
                                        <span class="crm-status crm-status-success">
                                            <i class="bi bi-check-circle"></i> Original
                                        </span>
                                    @endif
                                    @can('refund', $order)
                                        <form action="" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Refund</button>
                                        </form>
                                    @endcan
                                    @can('cancel', $order)
                                        <form action="" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="crm-empty">
                                        <i class="bi bi-wallet2 d-block"></i>
                                        No payments yet.
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
