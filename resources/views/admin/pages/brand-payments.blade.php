@extends('admin.layout.layout')

@section('title', 'Admin | Brand Payments')

@section('admin-content')



    <div class="container-fluid">

        {{-- PAGE TITLE --}}
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="fw-bold" style="color: #003C51;">Brand Payouts & Revenue</h1>
            </div>
        </div>
        <hr>

        {{-- TOP SUMMARY CARDS (GROSS / NET / REFUNDS / CHARGEBACKS BY PROVIDER) --}}
        <div class="row my-4">
            @foreach ($providerStats as $provider => $ps)
                <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                    {{-- Provider Header --}}
                    <div class="card mb-3">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3">
                                @if ($provider === 'stripe')
                                    <img src="https://images.icon-icons.com/2699/PNG/512/stripe_logo_icon_167962.png"
                                        style="width:55px;">
                                @elseif ($provider === 'paypal')
                                    <img src="https://cdn-icons-png.flaticon.com/512/174/174861.png" style="width:55px;">
                                @else
                                    <img src="https://cdn-icons-png.flaticon.com/128/12201/12201263.png"
                                        style="width:55px;">
                                @endif
                            </div>
                            <div>
                                <h4 class="font-weight-bold m-0">
                                    {{ ucfirst($provider) ?? 'N/A' }} Performance
                                </h4>
                                <small class="text-muted">Provider Revenue Summary</small>
                            </div>
                        </div>
                    </div>

                    {{-- Inner Cards --}}
                    <div class="row">

                        {{-- NET Revenue (highlight) --}}
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-success">
                                <div class="card-body text-center">
                                    <h6 class="text-success font-weight-bold">Net Revenue</h6>
                                    <h3 class="text-success font-weight-bold mb-0">
                                        {{ money_cents($ps['net'] ?? 0) }}
                                    </h3>
                                    <small class="text-muted">After refunds & chargebacks</small>
                                </div>
                            </div>
                        </div>

                        {{-- Gross Revenue --}}
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body text-center">
                                    <h6 class="text-dark font-weight-bold">Gross Revenue</h6>
                                    <h4 class="text-dark mb-0">
                                        {{ money_cents($ps['gross'] ?? 0) }}
                                    </h4>
                                </div>
                            </div>
                        </div>

                        {{-- Refunds --}}
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-danger">
                                <div class="card-body text-center">
                                    <h6 class="text-danger font-weight-bold">Refunds</h6>
                                    <h4 class="text-danger mb-0">
                                        -{{ money_cents($ps['refunds'] ?? 0) }}
                                    </h4>
                                </div>
                            </div>
                        </div>

                        {{-- Chargebacks --}}
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-danger">
                                <div class="card-body text-center">
                                    <h6 class="text-danger font-weight-bold">Chargebacks</h6>
                                    <h4 class="text-danger mb-0">
                                        -{{ money_cents($ps['chargebacks'] ?? 0) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{-- Pipeline --}}
                    <div class="col-md-12 mb-3">
                        <div class="card shadow-sm border-danger">
                            <div class="card-body text-center">
                                <h6 class="text-info font-weight-bold">Pipeline (Unpaid):</h6>
                                <h4 class="text-dark mb-0">
                                    -<strong>{{ money_cents($providerPipeline[$provider] ?? 0) }}</strong>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        {{-- Forecast Revenue Cards for Providers --}}
        <div class="row my-4">
            {{-- Stripe Forecast --}}
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card shadow-sm border-info">
                    <div class="card-body text-center">
                        <h6 class="text-info font-weight-bold">Stripe Forecast Revenue</h6>
                        <h4 class="text-dark mb-0">
                            {{ money_cents($providerForecast['stripe'] ?? 0) }}
                        </h4>
                        <small class="text-muted">Forecast revenue for the next period</small>
                    </div>
                </div>
            </div>
            {{-- PayPal Forecast --}}
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card shadow-sm border-info">
                    <div class="card-body text-center">
                        <h6 class="text-info font-weight-bold">PayPal Forecast Revenue</h6>
                        <h4 class="text-dark mb-0">
                            {{ money_cents($providerForecast['paypal'] ?? 0) }}
                        </h4>
                        <small class="text-muted">Forecast revenue for the next period</small>
                    </div>
                </div>
            </div>
        </div>
        {{-- BRAND-LEVEL TABLE + ORDERS TABLE --}}
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">

                {{-- Brand payout summary --}}
                <table class="table table-bordered table-striped">
                    <thead class="text-white" style="background:#333;">
                        <tr>
                            <th>#</th>
                            <th>Brand</th>
                            <th>Total Orders</th>
                            <th>Total Paid</th>
                            <th>Total Due</th>
                            <th>Forecast Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brandPayments as $i => $brand)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    {{ $brand['brand_name'] }}
                                    @if (!empty($brand['brand_url']))
                                        : <a href="{{ $brand['brand_url'] }}" target="_blank">
                                            {{ $brand['brand_url'] }}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-success badge-sm text-white rounded-pill shadow-sm">
                                        <i class="fa fa-sync-alt me-1"></i> Original - {{ $brand['original_orders'] }}
                                    </span>
                                    -
                                    <span class="badge badge-danger badge-sm text-white rounded-pill shadow-sm">
                                        <i class="fa fa-sync-alt me-1"></i> Renewed - {{ $brand['renewal_orders'] }}
                                    </span>
                                </td>
                                <td>{{ number_format(($brand['total_paid'] ?? 0) / 100, 2) }} USD</td>
                                <td>{{ number_format(($brand['total_due'] ?? 0) / 100, 2) }} USD</td>
                                <td>{{ money_cents($brand['forecast'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No brand data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Orders Details --}}
                <h4 class="mt-5 mb-3">Orders Details</h4>
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $i => $order)
                            @php
                                $sellerUser = auth('seller')->user();
                                $isSeller = auth('seller')->check() && !auth('admin')->check();
                                $ownsOrder =
                                    $isSeller &&
                                    $sellerUser->id === $order->seller_id &&
                                    $sellerUser->brand_id === $order->brand_id;
                            @endphp
                            <tr>
                                <td>{{ $orders->firstItem() + $i }}</td>

                                <td>
                                    @if ($isSeller && $sellerUser->id == $order->seller->id)
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
                                <td>
                                    {{ number_format(($order->amount_paid ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}
                                </td>
                                <td>
                                    {{ number_format(($order->balance_due ?? 0) / 100, 2) }}
                                    {{ $order->currency ?? 'USD' }}
                                </td>
                                <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>
                                <td>
                                    @if ($order->order_type === 'renewal')
                                        <span class="badge badge-danger badge-sm text-white rounded-pill shadow-sm">
                                            <i class="fa fa-sync-alt me-1"></i> Renewed
                                        </span>
                                    @else
                                        <span class="badge badge-success badge-sm text-white rounded-pill shadow-sm">
                                            <i class="fa fa-sync-alt me-1"></i> Original
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
