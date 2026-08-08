@extends('admin.layout.layout')

@section('title', 'Admin | Payments')

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Payments</h1>
            <p>Each row is one gateway charge. Partial payments on the same order appear as separate rows.</p>
        </div>
        <div class="crm-page-actions">
            <form method="GET" class="crm-filter-bar mb-0">
                @php
                    $selectedStatus = request('status');
                    $selectedProvider = request('provider');
                @endphp
                @if (request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                <select name="provider" class="form-select crm-filter-select" onchange="this.form.submit()">
                    <option value="">All providers</option>
                    @foreach (\App\Services\Admin\AdminPaymentsService::PROVIDERS as $providerOption)
                        <option value="{{ $providerOption }}" @selected($selectedProvider === $providerOption)>
                            {{ ucfirst($providerOption) }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-select crm-filter-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Services\Admin\AdminPaymentsService::PAYMENT_STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>
                            {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                        </option>
                    @endforeach
                </select>
            </form>
            <form method="GET" class="crm-search-bar mb-0">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if (request('provider'))
                    <input type="hidden" name="provider" value="{{ request('provider') }}">
                @endif
                <input type="text" placeholder="Search txn, client, service..."
                    value="{{ request('q') }}" name="q" class="form-control">
                <button type="submit" class="btn btn-crm-teal"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Provider</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Order</th>
                            <th>Brand / Service</th>
                            <th>Client</th>
                            <th>Seller</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $i => $payment)
                            @php
                                $order = $payment->order;
                                $txnId = $payment->provider_payment_intent_id;
                                $provider = $payment->resolvedProvider();
                                $statusClass = match ($payment->status) {
                                    'succeeded' => 'crm-status-success',
                                    'pending' => 'crm-status-warning',
                                    'failed' => 'crm-status-danger',
                                    'refunded', 'partially_refunded' => 'crm-status-neutral',
                                    default => 'crm-status-neutral',
                                };
                            @endphp
                            <tr>
                                <td data-label="#">{{ $payments->firstItem() + $i }}</td>
                                <td data-label="Date">
                                    <div class="fw-semibold">{{ $payment->created_at->format('M j, Y') }}</div>
                                    <div class="text-muted small">{{ $payment->created_at->format('g:i A') }}</div>
                                    <div class="text-muted small">Pay #{{ $payment->id }}</div>
                                </td>
                                <td data-label="Provider">
                                    @include('admin.includes.payment-provider-badge', ['payment' => $payment])
                                </td>
                                <td data-label="Amount">
                                    <div class="fw-semibold">
                                        {{ number_format(($payment->amount ?? 0) / 100, 2) }}
                                        {{ $payment->currency ?? 'USD' }}
                                    </div>
                                    @if ((int) ($payment->refunded_amount ?? 0) > 0)
                                        <div class="text-muted small">
                                            Refunded: {{ number_format($payment->refunded_amount / 100, 2) }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="crm-status {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                    </span>
                                </td>
                                <td data-label="Transaction ID">
                                    @if ($txnId)
                                        <code class="small" title="{{ $txnId }}">
                                            {{ \Illuminate\Support\Str::limit($txnId, 22) }}
                                        </code>
                                        <div class="text-muted small">
                                            {{ $provider === 'stripe' ? 'Stripe PI' : ($provider === 'paypal' ? 'PayPal capture' : 'Gateway ref') }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Order">
                                    @if ($order)
                                        @if ($tenantHasSupportTickets ?? false)
                                            <a href="{{ route('admin.order-tickets.get', $order) }}"
                                                class="fw-semibold text-decoration-none">
                                                #{{ $order->id }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">#{{ $order->id }}</span>
                                        @endif
                                        @if ($order->order_type === 'renewal')
                                            <span class="crm-status crm-status-neutral ms-1">Renewal</span>
                                        @endif
                                        @if ($payment->payment_link_id)
                                            <div class="text-muted small">Link #{{ $payment->payment_link_id }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Brand">
                                    {{ $order?->brand?->brand_name ?? '—' }}
                                    <p class="text-muted small mb-0">{{ $order?->service_name ?? '—' }}</p>
                                </td>
                                <td data-label="Client">
                                    <div class="fw-semibold">{{ $order?->client?->name ?? ($order?->buyer_name ?? '—') }}</div>
                                    <div class="text-muted small">
                                        {{ $order?->client?->email ?? ($order?->buyer_email ?? '—') }}
                                    </div>
                                </td>
                                <td data-label="Seller">
                                    <div class="fw-semibold">{{ $order?->seller?->name ?? '—' }}</div>
                                    <div class="text-muted small">
                                        {{ $order?->seller?->email ?? ($order?->seller?->sudo_name ?? '—') }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="crm-empty">
                                        <i class="bi bi-credit-card d-block"></i>
                                        No payment transactions yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="crm-pagination">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>

@endsection
