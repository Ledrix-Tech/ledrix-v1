@extends('clients.layouts.layout')

@section('title', 'Invoice #' . str_pad($order->id, 6, '0', STR_PAD_LEFT) . ' | Client Portal')

@push('styles')
    <style>
        :root {
            --brand-logo-watermark: url('{{ asset(config('branding.logo')) }}');
        }
    </style>
@endpush

@section('client-content')
    <div class="crm-page-header">
        <div>
            <a href="{{ route('client.invoice.get') }}" class="crm-back"><i class="bi bi-arrow-left"></i> Back to invoices</a>
            <h1>Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h1>
            <p>Issued {{ $order->created_at?->toFormattedDateString() }}</p>
        </div>
        <div class="crm-page-actions">
            <button type="button" id="generate-pdf" class="btn btn-crm-outline">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
        </div>
    </div>

    <div class="crm-card client-invoice-card invoiceCard">
        <div class="client-invoice-watermark" aria-hidden="true"></div>
        <div class="crm-card-body">
            <div class="row g-4">
                <div class="col-lg-7">
                    <h2 class="h5 fw-bold mb-3">Summary</h2>
                    <table class="table table-sm mb-4">
                        <tbody>
                            <tr>
                                <th scope="row" class="text-muted" style="width:100px">To</th>
                                <td>{{ $client->name ?? ($order->buyer_name ?? '—') }}</td>
                                <td class="text-muted">{{ $client->email ?? ($order->buyer_email ?? '—') }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">From</th>
                                <td>{{ $order->brand->brand_name ?? '—' }}</td>
                                <td class="text-muted">{{ config('mail.from.address') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $order->service_name }}</td>
                                    <td class="text-end">{{ money_cents($order->unit_amount, $order->currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($order->payments->count())
                        <h3 class="h6 fw-bold mb-2">Payments</h3>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered crm-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->payments as $p)
                                        <tr>
                                            <td>{{ $p->created_at?->toDayDateTimeString() }}</td>
                                            <td class="text-end">{{ money_cents($p->amount, $p->currency) }}</td>
                                            <td>{{ ucfirst($p->status) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="col-lg-5">
                    <div class="crm-card mb-3 pay-link">
                        <div class="crm-card-body">
                            <p class="fs-3 fw-bold mb-2">{{ money_cents($order->unit_amount, $order->currency) }}</p>
                            @include('clients.includes.status-badge', ['status' => $order->status])
                            <p class="text-muted small mt-2 mb-0">
                                Paid: {{ money_cents($order->amount_paid, $order->currency) }} ·
                                Due: {{ money_cents($order->balance_due, $order->currency) }}
                            </p>
                        </div>
                    </div>

                    <div class="crm-card mb-3">
                        <div class="crm-card-body">
                            <p class="mb-2"><i class="bi bi-person me-2 text-muted"></i>{{ $client->name ?? ($order->buyer_name ?? '—') }}</p>
                            <p class="mb-2"><i class="bi bi-calendar3 me-2 text-muted"></i>{{ $order->created_at?->toFormattedDateString() }}</p>
                            <p class="mb-0"><i class="bi bi-folder2 me-2 text-muted"></i>{{ $order->service_name }}</p>
                        </div>
                    </div>

                    <div class="crm-card mb-3">
                        <div class="crm-card-body">
                            <p class="d-flex justify-content-between mb-2"><span class="text-muted">Invoice created</span><span>{{ $order->created_at?->toFormattedDateString() }}</span></p>
                            <p class="d-flex justify-content-between mb-2"><span class="text-muted">Last link issued</span><span>{{ optional($order->paymentLinks->last()?->last_issued_at)?->toFormattedDateString() ?? '—' }}</span></p>
                            <p class="d-flex justify-content-between mb-0"><span class="text-muted">Invoice paid</span><span>{{ $order->paid_at?->toFormattedDateString() ?? '—' }}</span></p>
                        </div>
                    </div>

                    @php
                        $due = (int) ($order->balance_due ?? 0);
                        $currency = $order->currency ?? 'USD';
                    @endphp

                    <div class="pay-link">
                        @if ($due > 0)
                            @if (!empty($latestActiveLink) && !empty($payUrl))
                                <a href="{{ $payUrl }}"
                                    class="btn btn-crm-primary w-100" target="_blank" rel="noopener">
                                    Pay {{ number_format(($latestActiveLink->unit_amount ?? 0) / 100, 2) }} {{ $currency }}
                                </a>
                                @if ($latestActiveLink->expires_at)
                                    <p class="text-muted small mt-2 mb-0">Link expires {{ $latestActiveLink->expires_at->diffForHumans() }}</p>
                                @endif
                                <p class="text-muted small">Outstanding: {{ number_format($due / 100, 2) }} {{ $currency }}</p>
                            @else
                                <div class="alert alert-warning mb-0">
                                    Balance due ({{ number_format($due / 100, 2) }} {{ $currency }}), but no active payment link yet.
                                    Please contact your seller.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-success mb-0">This invoice is fully paid. Thank you!</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.getElementById('generate-pdf')?.addEventListener('click', function () {
            const btn = this;
            const payLinks = document.querySelectorAll('.pay-link');
            btn.disabled = true;
            payLinks.forEach(el => el.style.display = 'none');

            html2canvas(document.querySelector('.invoiceCard'), {
                scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false
            }).then(function (canvas) {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const imgHeight = (canvas.height * pageWidth) / canvas.width;
                pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, pageWidth, imgHeight);
                pdf.save('invoice-{{ $order->id }}.pdf');
            }).finally(function () {
                btn.disabled = false;
                payLinks.forEach(el => el.style.display = '');
            });
        });
    </script>
@endpush
