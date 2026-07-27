@extends('sellers.layout.layout')

@section('title', 'Seller | Payment Links')

@section('sellers-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Payments</h1>

                    <div class="d-flex">
                        <div class="examplesearch-form mx-3">
                            <form method="GET" class="flex gap-2">
                                <div class="d-flex">
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="">All statuses</option>
                                    </select>
                                    <button class="btn btn-success bg-gradient-3" type="submit">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">

                <table class="table table-striped">
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
                            <th>Paid at</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border">
                        @forelse($orders as $i => $order)
                            @php
                                $seller = auth('seller')->user();
                                $isSeller = auth('seller')->check() && !auth('admin')->check();
                                $ownsOrder =
                                    $isSeller &&
                                    $seller->id === $order->seller_id &&
                                    $seller->brand_id === $order->brand_id;
                                // dd($order,$seller, $isSeller, $ownsOrder);
                            @endphp
                            <tr>
                                <td>{{ $orders->firstItem() + $i }}</td>
                                <td>
                                    @if ($isSeller && $seller->id == $order->seller->id)
                                        <div class="text-muted fw-semibold">It's your's</div>
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
                                <td>{{ number_format(($order->amount_paid ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}</td>
                                <td>{{ number_format(($order->balance_due ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }} </td>
                                <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>
                                <td class="text-end">
                                    @if ($order->order_type === 'renewal')
                                        <span class="badge badge-danger badge-sm text-white rounded-pill shadow-sm">
                                            <i class="fa fa-sync-alt me-1"></i> Renewed
                                        </span>
                                    @else
                                        <span class="badge badge-success badge-sm text-white rounded-pill shadow-sm">
                                            <i class="fa fa-sync-alt me-1"></i> Original
                                        </span>
                                    @endif
                                    @can('refund', $order)
                                        <form action="" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Refund</button>
                                        </form>
                                    @endcan
                                    @can('cancel', $order)
                                        <form action="" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">
                                    <div class="text-center alert alert-info m-0">
                                        <h6>You don't have any payments yet !!!</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

    <script></script>

@endsection
