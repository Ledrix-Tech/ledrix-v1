@extends('central.layout.layout')



@section('title', 'Ledrix | Pending Subscription Payments')



@section('central-content')



    <div class="sa-page-header">

        <div>

            <h1>Pending Subscription Payments</h1>

            <p>Verify bank transfers on your Meezan statement before confirming. Never confirm without matching amount + reference.</p>

        </div>

    </div>



    @if (session('success'))

        <div class="alert alert-success">{{ session('success') }}</div>

    @endif

    @if (session('error'))

        <div class="alert alert-danger">{{ session('error') }}</div>

    @endif



    <div class="sa-card">

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

                                <td data-label="Amount"><strong>{{ strtoupper($payment->currency) }} {{ number_format((float) $payment->amount, 0) }}</strong></td>

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
                                    @php
                                        $confirmMsg = 'Confirm ONLY if your Meezan statement shows PKR '
                                            . number_format((float) $payment->amount, 0)
                                            . ' with Ledrix ref ' . $payment->transaction_id
                                            . ($bankTxn ? ' and bank txn ' . $bankTxn : '')
                                            . '. Activate subscription?';
                                    @endphp
                                    <form method="POST" action="{{ route('super-admin.subscription-payments.confirm', $payment->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                            onclick="return confirm(@js($confirmMsg))">
                                            Confirm &amp; activate
                                        </button>
                                    </form>
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="text-center text-muted py-4">No pending subscription payments.</td>

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



@endsection

