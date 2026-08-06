@extends('central.layout.layout')

@section('title', 'Ledrix | Invoice '.$invoice->invoice_number)

@section('central-content')
    @php
        $currency = strtoupper($invoice->currency ?? 'USD');
        $decimals = $currency === 'USD' ? 2 : 0;
    @endphp

    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.tenant.show', $tenant->id) }}" class="text-muted small text-decoration-none">&larr; Tenant</a>
            <h1 class="mt-1">Invoice {{ $invoice->invoice_number }}</h1>
            <p>{{ $tenant->name }} · {{ ucfirst($invoice->status) }}</p>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
    </div>

    <div class="sa-card">
        <div class="sa-card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><strong>Plan</strong><br>{{ $invoice->plan_name ?? '—' }}</div>
                <div class="col-md-4"><strong>Cycle</strong><br>{{ ucfirst($invoice->billing_cycle ?? 'monthly') }}</div>
                <div class="col-md-4"><strong>Gateway</strong><br>{{ $payment ? ucfirst(str_replace('_', ' ', $payment->gateway)) : '—' }}</div>
                <div class="col-md-4"><strong>Issued</strong><br>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</div>
                <div class="col-md-4"><strong>Due</strong><br>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</div>
                <div class="col-md-4"><strong>Paid</strong><br>{{ $invoice->paid_at?->format('M d, Y H:i') ?? '—' }}</div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ledrix CRM — {{ $invoice->plan_name ?? 'Subscription' }}</td>
                        <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->amount, $decimals) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-end">{{ $currency }} {{ number_format((float) $invoice->total_amount, $decimals) }}</th>
                    </tr>
                </tfoot>
            </table>

            @if ($payment?->transaction_id)
                <p class="small text-muted mb-0">Reference: <code>{{ $payment->transaction_id }}</code></p>
            @endif
        </div>
    </div>
@endsection
