@extends('central.layout.layout')

@section('title', 'Ledrix | Subscription Payments')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Subscription Payments</h1>
            <p>Confirm pending Meezan / Payoneer transfers after verifying your statement. Automated Stripe / PayFast payments are listed below for reference.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="sa-card mb-4">
        <div class="sa-card-header">
            <h4 class="mb-0">Pending manual payments</h4>
        </div>
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Tenant</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Ledrix payment ID</th>
                            <th>Bank txn ID (tenant)</th>
                            <th>Reported</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                $bankTxn = $payment->customerReportedTxnId();
                                $reportedAt = $payment->customerReportedAt();
                                $confirmMsg = 'Confirm ONLY if your statement shows '
                                    . strtoupper($payment->currency) . ' '
                                    . number_format((float) $payment->amount, 2)
                                    . ' with Ledrix ref ' . $payment->transaction_id
                                    . ($bankTxn ? ' and bank txn ' . $bankTxn : '')
                                    . '. Activate subscription?';
                            @endphp
                            <tr class="{{ $bankTxn ? 'table-warning' : '' }}">
                                <td data-label="#">{{ $payment->id }}</td>
                                <td data-label="Method">{{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}</td>
                                <td data-label="Status">
                                    @if ($bankTxn)
                                        <span class="badge bg-warning text-dark">Ready to verify</span>
                                    @else
                                        <span class="badge bg-secondary">Awaiting transfer</span>
                                    @endif
                                </td>
                                <td data-label="Tenant">
                                    @if ($payment->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $payment->tenant->id) }}">{{ $payment->tenant->name }}</a><br>
                                        <small class="text-muted">{{ $payment->tenant->email }}</small>
                                    @else
                                        —
                                    @endif
                                    <br><small class="text-muted">Inv: {{ $payment->invoice?->invoice_number ?? '—' }}</small>
                                </td>
                                <td data-label="Amount">
                                    <strong>{{ number_format((float) $payment->amount, 2) }}</strong>
                                </td>
                                <td data-label="Currency">
                                    <span class="badge bg-light text-dark border">{{ strtoupper($payment->currency) }}</span>
                                </td>
                                <td data-label="Ledrix ID"><code>{{ $payment->transaction_id }}</code></td>
                                <td data-label="Bank txn">
                                    @if ($bankTxn)
                                        <code>{{ $bankTxn }}</code>
                                        @if (! empty($payment->payload['customer_reported_note']))
                                            <br><small class="text-muted">{{ $payment->payload['customer_reported_note'] }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">Not submitted yet</span>
                                    @endif
                                </td>
                                <td data-label="Reported">
                                    @if ($reportedAt)
                                        {{ \Illuminate\Support\Carbon::parse($reportedAt)->format('M d, H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Action">
                                    @if (auth('super_admin')->user()?->isAdmin())
                                        <form method="POST" action="{{ route('super-admin.subscription-payments.confirm', $payment->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm(@js($confirmMsg))">
                                                Confirm &amp; activate
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No pending manual subscription payments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payments->hasPages())
                <div class="sa-pagination">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-header">
            <h4 class="mb-0">Recent automated payments (Stripe / PayFast)</h4>
        </div>
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Tenant</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Reference</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($automatedPayments as $payment)
                            @php
                                $autoStatusClass = match ($payment->status) {
                                    'succeeded', 'paid' => 'success',
                                    'pending' => 'warning',
                                    'failed' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td data-label="#">{{ $payment->id }}</td>
                                <td data-label="Gateway">{{ ucfirst($payment->gateway) }}</td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $autoStatusClass }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td data-label="Tenant">
                                    @if ($payment->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $payment->tenant->id) }}">{{ $payment->tenant->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Amount">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td data-label="Currency">
                                    <span class="badge bg-light text-dark border">{{ strtoupper($payment->currency) }}</span>
                                </td>
                                <td data-label="Reference"><code>{{ $payment->transaction_id }}</code></td>
                                <td data-label="Updated">{{ $payment->updated_at?->format('M d, Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No recent Stripe / PayFast payments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
