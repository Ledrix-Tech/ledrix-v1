@extends('sellers.layout.layout')

@section('title', 'Seller | Order & Renewals')

@section('sellers-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Order & Renewal's</h1>
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

                <div class="row">
                    <!-- Original Orders -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body p-0">
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
                                                <th>Paid at</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $seller = auth('seller')->user();
                                                $isSeller = auth('seller')->check() && !auth('admin')->check();
                                                $ownsOrder =
                                                    $isSeller &&
                                                    $seller->id === $order->seller_id &&
                                                    $seller->brand_id === $order->brand_id;
                                            @endphp

                                            <tr>
                                                <td>{{ $order->id }}</td>

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

                                                <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>

                                                <td>
                                                    @if ($order->balance_due <= 0)
                                                        @if (isFrontSeller() || isAdmin())
                                                            <a
                                                                href="{{ route('renew-order-link', [
                                                                    'brand' => $order->brand_id,
                                                                    'lead' => $order->lead_id,
                                                                    'order' => $order->id,
                                                                    'type' => 'renewal',
                                                                ]) }}">
                                                                <button type="button"
                                                                    class="badge btn-sm btn-outline-success">
                                                                    Renew
                                                                </button>
                                                            </a>
                                                        @endif
                                                    @else
                                                        <button type="button" class="badge badge-sm badge-info">
                                                            Pending
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Renewal Orders -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-light text-white text-center">
                                <h5 class="mb-0">Renewed</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="invoiceTable">
                                        <thead class="text-white" style="background:#000;">
                                            <tr>
                                                <th>#</th>
                                                <th>Service</th>
                                                <th>Total</th>
                                                <th>Dues</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                                <th>Paid At</th>
                                                <th class="text-end">PayLink</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($renewals as $order)
                                                <tr>
                                                    <td>{{ $order->id }}</td>
                                                    <td>{{ $order->service_name }}</td>
                                                    <td>{{ number_format($order->unit_amount / 100, 2) }}
                                                    <td>{{ number_format($order->balance_due / 100, 2) }}
                                                        {{ $order->currency }}
                                                    </td>
                                                    <td>{{ ucfirst($order->status) }}</td>
                                                    <td>
                                                        @php
                                                            $due = (int) ($order->balance_due ?? 0);
                                                            $sellerUser = auth('seller')->user();
                                                            $adminUser = auth('admin')->check();

                                                            $isAdmin = $adminUser !== null;
                                                            $isFront =
                                                                $sellerUser &&
                                                                ($sellerUser->role ?? $sellerUser->is_seller) ===
                                                                    'front_seller';
                                                            // front seller can generate link for ANY order in their brand
                                                            $sameBrand =
                                                                $sellerUser &&
                                                                (int) $sellerUser->brand_id === (int) $order->brand_id;

                                                            $canGenerateAsFront = $isFront && $sameBrand;
                                                            $canGenerateAsAdmin = $isAdmin;
                                                            $canGenerate =
                                                                ($canGenerateAsFront || $canGenerateAsAdmin) &&
                                                                $due > 0 &&
                                                                $order->status !== 'paid';
                                                        @endphp
                                                        @if ($canGenerate)
                                                            @if (isFrontSeller() || isAdmin())
                                                                <a
                                                                    href="{{ route('generate-link-form', [
                                                                        'brand' => $order->brand_id,
                                                                        'lead' => $order->lead_id,
                                                                        'order' => $order->id,
                                                                        'type' => 'renewal',
                                                                    ]) }}">
                                                                    <button type="button" class="badge badge-info">
                                                                        <i class="fa fa-plus-circle"></i> Generate Link
                                                                    </button>
                                                                </a>
                                                                <small class="d-block text-muted mt-2">
                                                                    Due: {{ number_format($due / 100, 2) }}
                                                                    {{ $order->currency ?? 'USD' }}
                                                                </small>
                                                            @endif
                                                        @else
                                                            @if ($due <= 0)
                                                                <span class="badge badge-success">
                                                                    <i class="fa fa-check-circle"></i> Paid in Full
                                                                </span>
                                                            @else
                                                                <button type="button" class="badge badge-secondary"
                                                                    disabled>
                                                                    <i class="fa fa-lock"></i> Pending
                                                                </button>
                                                            @endif
                                                        @endif

                                                    </td>
                                                    <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>
                                                    <td>
                                                        @if ($order->latestPaymentLink->is_active_link)
                                                            <a href="javascript:void(0);"
                                                                class="badge badge-success btn-sm togglePaylink"
                                                                data-toggle="tooltip"
                                                                data-id="{{ $order->latestPaymentLink->id }}"
                                                                data-status="false">
                                                                Active
                                                            </a>
                                                            @if ($order->latestPaymentLink->last_issued_url)
                                                                <button type="button"
                                                                    class="badge btn-outline-info copyBtn"
                                                                    data-url="{{ $order->latestPaymentLink->last_issued_url }}">
                                                                    Copy
                                                                </button>
                                                            @endif
                                                        @else
                                                            <a href="javascript:void(0);"
                                                                class="badge badge-danger btn-sm togglePaylink"
                                                                data-toggle="tooltip"
                                                                data-id="{{ $order->latestPaymentLink->id }}"
                                                                data-status="true">
                                                                Inactive
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="12">
                                                        <div class="text-center alert alert-info m-0">
                                                            <h6>You don't have any orders yet !!!</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        $(document).on("click", ".togglePaylink", function() {
            let id = $(this).data("id");
            let newStatus = $(this).data("status");

            $.ajax({
                url: "{{ route('change.paylink-status') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    is_active_link: newStatus
                },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message);
                        setTimeout(() => location.reload(), 800);
                    }
                }
            });
        });


        // copy logic
        $(document).on("click", ".copyBtn", function() {
            let btn = $(this);
            let url = btn.data("url");

            // Copy to clipboard
            navigator.clipboard.writeText(url).then(() => {

                // Change to green + text
                btn.removeClass("badge-info").addClass("badge-success").text("Copied!");

                // Revert after 3 seconds
                setTimeout(() => {
                    btn.removeClass("badge-success").addClass("badge-info").text("Copy");
                }, 3000);
            });
        });
    </script>

@endsection
