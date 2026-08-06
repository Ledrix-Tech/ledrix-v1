@php
    $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin';
    $currency = strtoupper($invoice->currency ?? $payment?->currency ?? 'USD');
    $decimals = $currency === 'USD' ? 2 : 0;
@endphp

<main class="{{ $isAdminOrg ? 'pb-4' : 'py-5' }}">
    <div class="{{ $isAdminOrg ? '' : 'container' }}" style="{{ $isAdminOrg ? '' : 'max-width: 800px;' }}">
        @if ($isAdminOrg)
            <div class="crm-page-header mb-4">
                <div>
                    <h1>Invoice {{ $invoice->invoice_number }}</h1>
                    <p>{{ $tenant->name }} · {{ ucfirst($invoice->status) }}</p>
                </div>
                <div class="crm-page-actions">
                    <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">&larr; Billing</a>
                    <button type="button" class="btn btn-crm-primary btn-sm" onclick="window.print()">Print</button>
                </div>
            </div>
        @else
            <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <a href="{{ org_route('billing') }}" class="text-muted small text-decoration-none">&larr; Back to billing</a>
                    <h4 class="mb-0 mt-1">Invoice {{ $invoice->invoice_number }}</h4>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <div class="text-muted small">Billed to</div>
                        <strong>{{ $tenant->name }}</strong><br>
                        <span class="text-muted">{{ $tenant->email }}</span>
                    </div>
                    <div class="text-md-end">
                        <div class="text-muted small">Status</div>
                        @php
                            $statusClass = match ($invoice->status) {
                                'paid' => 'success',
                                'issued' => 'warning',
                                'void' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="text-muted small">Plan</div>
                        <div>{{ $invoice->plan_name ?? $tenant->plan?->name ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Billing cycle</div>
                        <div>{{ ucfirst($invoice->billing_cycle ?? $payment?->billing_cycle ?? 'monthly') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Gateway</div>
                        <div>{{ $payment ? ucfirst(str_replace('_', ' ', $payment->gateway)) : '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Issued</div>
                        <div>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Due</div>
                        <div>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Paid</div>
                        <div>{{ $invoice->paid_at?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Ledrix CRM — {{ $invoice->plan_name ?? 'Subscription' }}
                                    <br><small class="text-muted">{{ ucfirst($invoice->billing_cycle ?? 'monthly') }} subscription</small>
                                </td>
                                <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->amount, $decimals) }}</td>
                            </tr>
                            @if ((float) $invoice->tax_amount > 0)
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->tax_amount, $decimals) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-end">{{ $currency }} {{ number_format((float) $invoice->total_amount, $decimals) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($payment?->transaction_id)
                    <div class="mt-4 pt-3 border-top small text-muted">
                        Payment reference: <code>{{ $payment->transaction_id }}</code>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>
