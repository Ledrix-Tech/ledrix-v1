@extends('sellers.layout.layout')

@section('title', 'Seller | Orders')

@section('sellers-content')
    <div class="crm-page-header">
        <div>
            <h1>Orders</h1>
            <p>View and manage client orders</p>
        </div>
        <div class="crm-page-actions">
            <form action="" method="GET" class="crm-search-bar mb-0">
                <input type="text" placeholder="Search orders..." value="{{ request('q', request('search')) }}" name="q"
                    class="form-control">
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
                            <th>Brand / Service</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment</th>
                            <th>Paid at</th>
                            <th class="text-end">Actions</th>
                            <th>Pay link</th>
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
                                <td data-label="Brand">
                                    {{ $order->brand->brand_name ?? '—' }}
                                    <p class="text-muted small mb-0">{{ $order->service_name ?? '—' }}</p>
                                </td>
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
                                    @include('sellers.includes.order-payment-cell', ['order' => $order])
                                </td>
                                <td data-label="Paid at">
                                    {{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}
                                </td>
                                <td data-label="Actions" class="text-end">
                                    @include('sellers.includes.order-actions', ['order' => $order])
                                </td>
                                <td data-label="Pay link">
                                    @include('sellers.includes.order-paylink-cell', ['order' => $order])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="crm-empty">
                                        <i class="bi bi-cart-x d-block"></i>
                                        No orders yet.
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
    <script>window.LedrixPaylinkToggleUrl = @json(route('change.paylink-status'));</script>
    <script src="{{ asset('seller-assets/js/orders.js') }}"></script>
@endpush
