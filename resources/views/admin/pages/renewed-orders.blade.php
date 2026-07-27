@extends('admin.layout.layout')

@section('title', 'Admin | Order & Renewals')

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Order & renewals</h1>
            <p>Original order and renewal payment history</p>
        </div>
        <div class="crm-page-actions">
            <a href="{{ route('admin.orders.get') }}" class="btn btn-sm btn-crm-outline">
                <i class="bi bi-arrow-left me-1"></i> All orders
            </a>
        </div>
    </div>

    <div class="crm-card mb-4">
        <div class="crm-section-title">
            <i class="bi bi-receipt me-1"></i> Original order
        </div>
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
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
                            <th>Status</th>
                            <th class="text-end">Renewal</th>
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
                            <td data-label="#">{{ $order->id }}</td>
                            <td data-label="Seller">
                                @if ($ownsOrder)
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
                            <td data-label="Status">
                                @if ($order->balance_due <= 0)
                                    <span class="crm-status crm-status-success">
                                        <i class="bi bi-check-circle-fill"></i> Paid in full
                                    </span>
                                @else
                                    <span class="crm-status crm-status-warning">
                                        <i class="bi bi-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td data-label="Renewal" class="text-end">
                                @if ((int) $order->balance_due <= 0 && $order->lead_id)
                                    @include('admin.includes.order-payment-cell', [
                                        'order' => $order,
                                        'renewalType' => 'renewal',
                                    ])
                                @else
                                    <span class="crm-status crm-status-neutral">
                                        Pay original first
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-section-title">
            <i class="bi bi-arrow-repeat me-1"></i> Renewal orders
        </div>
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Service</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Paid at</th>
                            <th>Pay link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($renewals as $renewal)
                            <tr>
                                <td data-label="#">{{ $renewal->id }}</td>
                                <td data-label="Service">{{ $renewal->service_name }}</td>
                                <td data-label="Total">
                                    {{ number_format($renewal->unit_amount / 100, 2) }}
                                    {{ $renewal->currency }}
                                </td>
                                <td data-label="Due">
                                    {{ number_format($renewal->balance_due / 100, 2) }}
                                    {{ $renewal->currency }}
                                </td>
                                <td data-label="Status">
                                    <span class="crm-status crm-status-info">{{ ucfirst($renewal->status) }}</span>
                                </td>
                                <td data-label="Payment">
                                    @include('admin.includes.order-payment-cell', [
                                        'order' => $renewal,
                                        'renewalType' => 'renewal',
                                    ])
                                </td>
                                <td data-label="Paid at">
                                    {{ optional($renewal->paid_at)->toDayDateTimeString() ?? '—' }}
                                </td>
                                <td data-label="Pay link">
                                    @include('admin.includes.order-paylink-cell', ['order' => $renewal])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crm-empty py-4">
                                        <i class="bi bi-arrow-repeat d-block"></i>
                                        <p class="mb-2">No renewal orders yet.</p>
                                        @if ((int) $order->balance_due <= 0 && $order->lead_id)
                                            <a href="{{ route('renew-order-link', [
                                                'brand' => $order->brand_id,
                                                'lead' => $order->lead_id,
                                                'order' => $order->id,
                                                'type' => 'renewal',
                                            ]) }}"
                                                class="btn btn-sm btn-crm-teal">
                                                <i class="bi bi-link-45deg me-1"></i> Create first renewal link
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>window.LedrixPaylinkToggleUrl = @json(route('change.paylink-status'));</script>
    <script src="{{ asset('admin-assets/js/orders.js') }}"></script>
@endpush
