@if ($invoices->isNotEmpty())
    <div class="table-responsive p-3">
        <table class="table table-sm table-hover mb-0 align-middle">            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th>Reference</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->plan_name ?? '—' }}</td>
                        <td>{{ strtoupper($invoice->currency) }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td>
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
                        <td>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if ($invoice->payment)
                                <code class="small">{{ $invoice->payment->transaction_id }}</code>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <a href="{{ org_route('billing.invoice.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted mb-0 p-3">No invoices yet.</p>
@endif