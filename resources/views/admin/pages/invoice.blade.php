@extends('admin.layout.layout')

@section('title', 'Admin | Invoice #' . str_pad($order->id, 6, '0', STR_PAD_LEFT))

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/invoice.css') }}">
@endpush

@section('admin-content')

    @php
        $clientName = $client->name ?? ($order->buyer_name ?? '—');
        $clientEmail = $client->email ?? ($order->buyer_email ?? '—');
        $clientPhone = $client->phone ?? '—';
        $due = (int) ($order->balance_due ?? 0);
        $currency = $order->currency ?? 'USD';
        $statusClass = match ($order->status) {
            'paid' => 'crm-status-success',
            'cancelled', 'canceled', 'refunded' => 'crm-status-danger',
            default => 'crm-status-warning',
        };
        $activePayUrl = $latestActiveLink
            ? ($latestActiveLink->last_issued_url ?? route('paylinks.show', $latestActiveLink->token))
            : null;
        $logoUrl = asset(config('branding.logo', 'admin-assets/dpm-logos/logo-ic.png'));
    @endphp

    <div class="crm-invoice-back-wrap crm-invoice-no-print">
        <a href="{{ route('admin.orders.get') }}" class="crm-invoice-back">
            <i class="bi bi-arrow-left"></i> Back to orders
        </a>
    </div>

    <div class="crm-page-header crm-invoice-no-print mb-3">
        <div>
            <h1>Order invoice</h1>
            <p>Full invoice details for order #{{ $order->id }}</p>
        </div>
    </div>

    <div class="crm-invoice-card invoiceCard" style="--crm-invoice-logo-url: url('{{ $logoUrl }}')">
        <div class="crm-invoice-watermark-logo" aria-hidden="true"></div>
        <div class="crm-invoice-inner">
            <div class="crm-invoice-top">
                <div>
                    <div class="crm-invoice-brand">
                        <div class="crm-invoice-brand-dots">
                            <span class="dot-purple"></span>
                            <span class="dot-green"></span>
                        </div>
                        <span class="crm-invoice-brand-name">Ledrix</span>
                    </div>
                    <h2>Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h2>
                    <p class="crm-invoice-meta">Issued on {{ $order->created_at?->toFormattedDateString() ?? '—' }}</p>
                    <div class="crm-invoice-meta-chips">
                        <span class="crm-invoice-chip">Order #{{ $order->id }}</span>
                        @if ($order->order_type)
                            <span class="crm-invoice-chip">{{ ucfirst($order->order_type) }}</span>
                        @endif
                        @if ($order->lead_id)
                            <span class="crm-invoice-chip">Lead #{{ $order->lead_id }}</span>
                        @endif
                    </div>
                </div>
                <button id="generate-pdf" type="button" class="btn btn-crm-primary crm-invoice-no-print">
                    <i class="fa fa-file-pdf-o me-1"></i> Download PDF
                </button>
            </div>

            <div class="row g-4" id="invoice-container">
                {{-- Left column --}}
                <div class="col-lg-7">
                    <div class="crm-invoice-section-title">Summary</div>
                    <table class="table table-borderless crm-invoice-summary-table mb-4">
                        <tbody>
                            <tr>
                                <th scope="row">To</th>
                                <td>{{ $clientName }}</td>
                                <td>{{ $clientEmail }}</td>
                            </tr>
                            @if ($clientPhone !== '—')
                                <tr>
                                    <th scope="row">Phone</th>
                                    <td colspan="2">{{ $clientPhone }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th scope="row">From</th>
                                <td>{{ $order->brand->brand_name ?? '—' }}</td>
                                <td>{{ config('mail.from.address') }}</td>
                            </tr>
                            @if ($order->brand?->brand_url)
                                <tr>
                                    <th scope="row">Brand URL</th>
                                    <td colspan="2">{{ $order->brand->brand_url }}</td>
                                </tr>
                            @endif
                            @if ($order->seller)
                                <tr>
                                    <th scope="row">Seller</th>
                                    <td>{{ $order->seller->name ?? '—' }}</td>
                                    <td>{{ $order->seller->email ?? ($order->seller->sudo_name ?? '—') }}</td>
                                </tr>
                            @endif
                            @if ($order->lead)
                                <tr>
                                    <th scope="row">Lead</th>
                                    <td>{{ $order->lead->name ?? '—' }}</td>
                                    <td>{{ $order->lead->email ?? '—' }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <div class="crm-invoice-section-title">Services</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm crm-invoice-table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $order->service_name ?? '—' }}</td>
                                    <td class="text-end">{{ money_cents($order->unit_amount, $order->currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($order->payments->count())
                        <div class="crm-invoice-section-title">Payments</div>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm crm-invoice-table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at?->toDayDateTimeString() }}</td>
                                            <td class="text-end">{{ money_cents($payment->amount, $payment->currency) }}</td>
                                            <td>{{ ucfirst($payment->provider ?? '—') }}</td>
                                            <td>
                                                <span class="crm-status {{ $payment->status === 'paid' ? 'crm-status-success' : 'crm-status-neutral' }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($order->paymentLinks->count())
                        <div class="crm-invoice-section-title">Payment links</div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-invoice-table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Issued</th>
                                        <th class="text-end">Amount</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Active</th>
                                        <th class="crm-invoice-no-print">Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->paymentLinks as $link)
                                        <tr>
                                            <td>{{ optional($link->last_issued_at ?? $link->created_at)->toDayDateTimeString() ?? '—' }}</td>
                                            <td class="text-end">{{ money_cents($link->unit_amount, $link->currency) }}</td>
                                            <td>{{ ucfirst($link->provider ?? '—') }}</td>
                                            <td>{{ ucfirst($link->status ?? '—') }}</td>
                                            <td>
                                                <span class="crm-status {{ $link->is_active_link ? 'crm-status-success' : 'crm-status-neutral' }}">
                                                    {{ $link->is_active_link ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="crm-invoice-no-print">
                                                @if ($link->last_issued_url)
                                                    <div class="crm-invoice-link-row">
                                                        <span class="crm-invoice-link-url">{{ Str::limit($link->last_issued_url, 40) }}</span>
                                                        <button type="button" class="btn btn-sm btn-crm-outline copyBtn"
                                                            data-url="{{ $link->last_issued_url }}">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Right sidebar --}}
                <div class="col-lg-5">
                    <div class="crm-invoice-summary-box">
                        <div class="crm-invoice-total">{{ money_cents($order->unit_amount, $order->currency) }}</div>
                        <span class="crm-status {{ $statusClass }} mt-2">{{ ucfirst($order->status) }}</span>
                        <div class="crm-invoice-balance">
                            Paid: {{ money_cents($order->amount_paid, $order->currency) }}<br>
                            Due: {{ money_cents($order->balance_due, $order->currency) }}
                        </div>
                    </div>

                    <div class="crm-invoice-summary-box">
                        <div class="crm-invoice-section-title mb-2">Details</div>
                        <ul class="crm-invoice-detail-list">
                            <li><i class="fa fa-user"></i> {{ $clientName }}</li>
                            <li><i class="fa fa-envelope"></i> {{ $clientEmail }}</li>
                            @if ($clientPhone !== '—')
                                <li><i class="fa fa-phone"></i> {{ $clientPhone }}</li>
                            @endif
                            <li><i class="fa fa-calendar"></i> {{ $order->created_at?->toFormattedDateString() ?? '—' }}</li>
                            <li><i class="fa fa-folder"></i> {{ $order->service_name ?? '—' }}</li>
                            @if (optional($lastIssuedLink)->last_issued_url)
                                <li class="pay-link">
                                    <i class="fa fa-link"></i>
                                    <span class="crm-invoice-paylink">{{ $lastIssuedLink->last_issued_url }}</span>
                                </li>
                            @endif
                        </ul>

                        <div class="crm-invoice-timeline">
                            <div class="crm-invoice-timeline-item">
                                <span class="label"><i class="fa fa-circle"></i> Invoice created</span>
                                <span class="date">{{ $order->created_at?->toFormattedDateString() ?? '—' }}</span>
                            </div>
                            <div class="crm-invoice-timeline-item">
                                <span class="label"><i class="fa fa-circle"></i> Last link issued</span>
                                <span class="date">
                                    {{ optional($lastIssuedLink?->last_issued_at ?? $lastIssuedLink?->created_at)?->toFormattedDateString() ?? '—' }}
                                </span>
                            </div>
                            <div class="crm-invoice-timeline-item">
                                <span class="label"><i class="fa fa-circle"></i> Invoice paid</span>
                                <span class="date">{{ $order->paid_at?->toFormattedDateString() ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="pay-link">
                            @if ($due > 0)
                                @if (!empty($latestActiveLink) && $activePayUrl)
                                    <div class="crm-invoice-pay-actions crm-invoice-no-print">
                                        <a href="{{ $activePayUrl }}" class="btn btn-crm-primary w-100" target="_blank"
                                            rel="noopener">
                                            <i class="bi bi-credit-card me-1"></i>
                                            Pay {{ number_format(($latestActiveLink->unit_amount ?? 0) / 100, 2) }}
                                            {{ $currency }}
                                            @if ($latestActiveLink->expires_at)
                                                <small class="d-block opacity-75">Expires
                                                    {{ $latestActiveLink->expires_at->diffForHumans() }}</small>
                                            @endif
                                        </a>
                                        <button type="button" class="btn btn-sm btn-crm-outline copyBtn w-100"
                                            data-url="{{ $activePayUrl }}">
                                            <i class="bi bi-clipboard"></i> Copy payment link
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Outstanding balance: {{ number_format($due / 100, 2) }} {{ $currency }}
                                    </small>
                                @else
                                    <div class="crm-invoice-alert crm-invoice-alert--warning mb-0">
                                        An installment is due ({{ number_format($due / 100, 2) }} {{ $currency }}),
                                        but no active payment link is available yet.
                                    </div>
                                @endif
                            @else
                                <div class="crm-invoice-alert crm-invoice-alert--success mb-0">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    This invoice is fully paid. Thank you!
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 pt-3 border-top">
                <small class="text-muted">Powered by <strong style="color:var(--crm-blue)">Ledrix</strong> CRM</small>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.getElementById('generate-pdf');

            document.querySelectorAll('.copyBtn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const url = btn.dataset.url;
                    if (!url) return;
                    navigator.clipboard.writeText(url).then(function() {
                        const original = btn.innerHTML;
                        btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
                        setTimeout(function() {
                            btn.innerHTML = original;
                        }, 2000);
                    });
                });
            });

            downloadBtn?.addEventListener('click', function() {
                document.querySelectorAll('.crm-invoice-no-print').forEach(function(el) {
                    el.dataset.wasVisible = el.style.display;
                    el.style.display = 'none';
                });

                const element = document.querySelector('.invoiceCard');
                const { jsPDF } = window.jspdf;

                html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function(canvas) {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const imgHeight = (canvas.height * pageWidth) / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 0;

                    pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft > 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }

                    pdf.save('ledrix-invoice-{{ $order->id }}.pdf');
                }).catch(function(err) {
                    console.error('PDF Error:', err);
                }).finally(function() {
                    document.querySelectorAll('.crm-invoice-no-print').forEach(function(el) {
                        el.style.display = el.dataset.wasVisible || '';
                    });
                });
            });
        });
    </script>
@endpush
