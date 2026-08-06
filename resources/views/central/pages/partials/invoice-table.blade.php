@if ($invoices->isNotEmpty())
    <div class="sa-table-wrap">
        <table class="table sa-table mb-0">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th>Paid</th>
                    <th>Reference</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td data-label="Invoice"><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td data-label="Plan">{{ $invoice->plan_name ?? '—' }}</td>
                        <td data-label="Amount">{{ strtoupper($invoice->currency) }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td data-label="Status">
                            @php
                                $invoiceStatusClass = match ($invoice->status) {
                                    'paid' => 'success',
                                    'issued' => 'warning',
                                    'void' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $invoiceStatusClass }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td data-label="Issued">{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</td>
                        <td data-label="Due">{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</td>
                        <td data-label="Paid">{{ $invoice->paid_at?->format('M d, Y') ?? '—' }}</td>
                        <td data-label="Reference">
                            @if ($invoice->payment)
                                <code class="small">{{ $invoice->payment->transaction_id }}</code>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="">
                            <a href="{{ route('super-admin.tenant.invoice.show', [$invoice->tenant_id, $invoice->id]) }}"
                                class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted mb-0 p-3">No invoices yet.</p>
@endif
