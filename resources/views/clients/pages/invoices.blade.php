@extends('clients.layouts.layout')

@section('title', 'Invoices | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <h1>Invoices</h1>
            <p>View payment history and outstanding balances for your orders.</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body">
            <form action="{{ route('client.invoice.get') }}" method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search package" name="package"
                        value="{{ request('package') }}">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Invoice #" name="invoice"
                        value="{{ request('invoice') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status" aria-label="Filter by status">
                        <option value="">Any status</option>
                        @foreach (['draft', 'pending', 'paid', 'in_progress', 'revision', 'completed', 'refunded', 'canceled'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>
                                {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-crm-primary w-100">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped crm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Brand</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td data-label="#">{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td data-label="Brand">
                                    <div class="fw-semibold">{{ $order->brand->brand_name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $order->buyer_name ?? ($order->client->name ?? '—') }}</div>
                                </td>
                                <td data-label="Service">{{ $order->service_name ?? '—' }}</td>
                                <td data-label="Status">@include('clients.includes.status-badge', ['status' => $order->status])</td>
                                <td data-label="Total">{{ money_cents($order->unit_amount, $order->currency) }}</td>
                                <td data-label="Paid">{{ money_cents($order->amount_paid, $order->currency) }}</td>
                                <td data-label="Due">{{ money_cents($order->balance_due, $order->currency) }}</td>
                                <td data-label="Type">
                                    @if ($order->order_type === 'renewal')
                                        <span class="crm-status crm-status-warning">Renewal</span>
                                    @else
                                        <span class="crm-status crm-status-neutral">Original</span>
                                    @endif
                                </td>
                                <td data-label="Actions" class="text-end">
                                    <a href="{{ route('client.invoice.details', $order) }}" class="btn btn-sm btn-crm-outline">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('client.raise-ticket.get', $order) }}" class="btn btn-sm btn-crm-primary">
                                        <i class="bi bi-plus-lg"></i> Ticket
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="d-flex justify-content-center mt-3">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
@endsection
