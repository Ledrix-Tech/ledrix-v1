@extends('sellers.layout.layout')

@section('title', 'Seller | PM Orders')

@section('sellers-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">PM Orders</h1>
                    </div>
                    <div class="examplesearch-form mx-3">
                        <form action="" method="" class="example">
                            <div class="d-flex">
                                <input type="text" placeholder="Search.." value="" name="search"
                                    class="form-control">
                                <button type="submit" class="btn text-white bg-gradient-3"><i
                                        class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table table-striped" id="invoiceTable">
                        <thead class="text-white" style="background:#000;">
                            <tr>
                                <th>#</th>
                                <th>Seller</th>
                                <th>Brand</th>
                                <th>Service</th>
                                <th>Client</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                                <th>Paid at</th>
                                <th class="text-end">Action</th>
                                <th class="text-end">Paylink</th>
                            </tr>
                        </thead>
                        <tbody class="border">
                            @forelse($orders as $i => $order)
                                @php
                                    $sellerUser = auth('seller')->user();
                                    $isSeller = auth('seller')->check();
                                    $role = $sellerUser->role ?? $sellerUser->is_seller;
                                    $isFront = $role === 'front_seller';
                                    $isPM = $role === 'pm'; // ❌ PM cannot generate link
                                    // For row highlighting text
                                    $ownsOrder =
                                        $isSeller &&
                                        $sellerUser->id === $order->seller_id &&
                                        $sellerUser->brand_id === $order->brand_id;

                                    $due = (int) ($order->balance_due ?? 0);
                                    $sameBrand = $sellerUser && (int) $sellerUser->brand_id === (int) $order->brand_id;
                                @endphp

                                <tr>
                                    <td>{{ $orders->firstItem() + $i }}</td>
                                    <td>
                                        @if ($ownsOrder)
                                            <div class="text-muted fw-semibold">It's yours</div>
                                        @else
                                            <div class="fw-semibold">{{ $order->seller->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                {{ $order->seller->email ?? ($order->seller->sudo_name ?? '—') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ $order->brand->brand_name ?? '—' }}</td>
                                    <td>{{ $order->service_name ?? '—' }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $order->client->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $order->client->email ?? ($order->buyer_email ?? '—') }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ number_format(($order->unit_amount ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}
                                    </td>

                                    <td>
                                        {{ number_format(($order->amount_paid ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}
                                    </td>

                                    <td>
                                        {{ number_format(($order->balance_due ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}
                                    </td>

                                    <td>
                                        @if ($due <= 0)
                                            <span class="badge badge-success">
                                                <i class="fa fa-check-circle"></i> Paid in Full
                                            </span>
                                        @else
                                            <button type="button" class="badge badge-secondary" disabled>
                                                <i class="fa fa-lock"></i> Pending
                                            </button>
                                        @endif
                                    </td>
                                    <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>

                                    <td class="text-end">
                                        @if ($tenantHasSupportTickets ?? false)
                                            <a href="{{ route('seller.order-tickets.get', $order) }}"
                                                style="text-decoration: none;">
                                                <badge type="button" class="badge badge-info">
                                                    <i class="fa fa-ticket"></i> Tickets
                                                </badge>
                                            </a>
                                        @endif
                                        @if ($tenantHasDualInvoicing ?? false)
                                            <a href="{{ route('seller.order.generate-invoice', $order) }}"><button
                                                    type="submit" class="badge btn-sm btn-outline-danger">invoice</button></a>
                                        @endif
                                        @if ($order->order_type === 'renewal')
                                            <span class="badge bg-danger text-white rounded-pill px-3 py-2 shadow-sm">
                                                <i class="fa fa-sync-alt me-1"></i> Renewed
                                            </span>
                                        @else
                                            <a href="{{ route('seller.renewed-orders.get', $order->id) }}">
                                                <badge type="button" class="badge badge-light">
                                                    <i class="fa fa-plus-circle"></i> Check
                                                </badge>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->latestPaymentLink)
                                            @if ($order->latestPaymentLink->is_active_link)
                                                <a href="javascript:void(0);" class="badge badge-success btn-sm togglePaylink"
                                                    data-toggle="tooltip" data-id="{{ $order->latestPaymentLink->id }}"
                                                    data-status="false">
                                                    Active
                                                </a>
                                                @if ($order->latestPaymentLink->last_issued_url)
                                                    <button type="button" class="badge btn-outline-info copyBtn"
                                                        data-url="{{ $order->latestPaymentLink->last_issued_url }}">
                                                        Copy
                                                    </button>
                                                @endif
                                            @else
                                                <a href="javascript:void(0);" class="badge badge-danger btn-sm togglePaylink"
                                                    data-toggle="tooltip" data-id="{{ $order->latestPaymentLink->id }}"
                                                    data-status="true">
                                                    Inactive
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted">
                                        No orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $orders->links() }}
                        <div hidden>
                            @if ($orders->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $orders->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $orders->url($orders->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $orders->currentPage(); $i <= $orders->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $orders->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $orders->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $orders->currentPage() == $orders->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $orders->url($orders->currentPage() + 1) }}">Next</a>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
