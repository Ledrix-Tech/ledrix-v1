@extends('admin.layout.layout')

@section('title', 'Admin | Upwork Order')

@section('admin-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Orders</h1>
                        <!-- It display CSV for both admin and white wolf -->
                        <a href="{{ route('export.csv', ['table' => 'upwork_orders', 'columns' => 'id,order_type,service_name,currency,unit_amount,amount_paid,balance_due,status,buyer_name,buyer_email,provider_session_id,provider_payment_intent_id,paid_at']) }}"
                            style="text-decoration: none;">
                            <button class="btn btn-sm bg-gradient-3" type="button">
                                <i class="fa fa-file-excel-o"></i> CSV
                            </button>
                        </a>
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
                                <th>Brand / Service</th>
                                <th>Client</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                                <th>Paid at</th>
                                <th class="text-end">Payment Link</th>
                            </tr>
                        </thead>
                        <tbody class="border">
                            @forelse($orders as $i => $order)
                                @php
                                    $due = (int) ($order->balance_due ?? 0);

                                    // ✅ Admin role check
                                    $adminUser = auth('admin')->user(); // null if not logged in
                                    $isAdmin = (bool) $adminUser;

                                    // ✅ only up_admin can generate
                                    $isUpAdmin = $adminUser && $adminUser->role === 'up_admin';

                                    $canGenerate = $isUpAdmin && $due > 0 && $order->status !== 'paid';

                                    // Payment link (ensure relation exists)
                                    $latestLink = $order->latestPaymentLink;
                                @endphp

                                <tr>
                                    <td>{{ $orders->firstItem() + $i }}</td>

                                    {{-- Brand + Service --}}
                                    <td>
                                        {{ $order->brand->brand_name ?? '—' }}
                                        <p class="text-sm text-muted small">{{ $order->service_name ?? '—' }}</p>
                                    </td>

                                    {{-- Client --}}
                                    <td>
                                        <div class="fw-semibold">{{ $order->client->name ?? '—' }}</div>
                                        <div class="text-muted small">{{ $order->client->email ?? '—' }}</div>
                                    </td>

                                    {{-- Totals --}}
                                    <td>{{ number_format(($order->unit_amount ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}</td>
                                    <td>{{ number_format(($order->amount_paid ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}</td>
                                    <td>{{ number_format(($order->balance_due ?? 0) / 100, 2) }}
                                        {{ $order->currency ?? 'USD' }}</td>

                                    {{-- Generate Link --}}
                                    <td>
                                        @if ($canGenerate)
                                            <a
                                                href="{{ route('upwork.link-generator.installment', ['order' => $order->id]) }}">
                                                <button type="button" class="badge badge-info">
                                                    <i class="fa fa-plus-circle"></i> Generate Link
                                                </button>
                                            </a>
                                            <small class="d-block text-muted mt-2">
                                                Due: {{ number_format($due / 100, 2) }} {{ $order->currency ?? 'USD' }}
                                            </small>
                                        @else
                                            @if ($due <= 0 || $order->status === 'paid')
                                                <span class="badge badge-success">
                                                    <i class="fa fa-check-circle"></i> Paid in Full
                                                </span>
                                            @else
                                                {{-- not up_admin OR not allowed --}}
                                                <button type="button" class="badge badge-secondary" disabled>
                                                    <i class="fa fa-lock"></i> Not Allowed
                                                </button>
                                            @endif
                                        @endif
                                    </td>

                                    <td>{{ optional($order->paid_at)->toDayDateTimeString() ?? '—' }}</td>

                                    {{-- Payment Link Status (keep this) --}}
                                    <td>
                                        @if ($latestLink)
                                            @if ($latestLink->is_active_link)
                                                <a href="javascript:void(0);"
                                                    class="badge badge-success btn-sm"
                                                    data-id="{{ $latestLink->id }}" data-status="false">
                                                    Active
                                                </a>
                                            @else
                                                <a href="javascript:void(0);"
                                                    class="badge badge-danger btn-sm"
                                                    data-id="{{ $latestLink->id }}" data-status="true">
                                                    Inactive
                                                </a>
                                            @endif
                                        @else
                                            <span class="badge badge-light">No Link</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10">
                                        <div class="text-center alert alert-info m-0">
                                            <h6>No Upwork orders yet.</h6>
                                        </div>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        $(document).on("click", ".togglePaylink", function() {
            let id = $(this).data("id");
            let newStatus = $(this).data("status");

            $.ajax({
                url: "",
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
