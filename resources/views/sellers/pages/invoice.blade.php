@extends('sellers.layout.layout')

@section('title', 'Seller | Invoice')

@section('sellers-content')

    <style>
        .invoice-section .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('{{ url('admin-assets/dpm-logos/dpm-fav.png') }}') center / 220px no-repeat;
            opacity: 0.20;
            z-index: 0;
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
        }

        .invoice-section>* {
            position: relative;
            z-index: 1;
        }
    </style>

    {{-- <div id="loading-screen"
        style="display: none;
                    position:fixed;
                    top:0; left:0;
                    width:100%; height:100%;
                    background:rgba(0,0,0,0.6);
                    z-index:9999;
                    display:flex;
                    flex-direction:column;
                    align-items:center;
                    justify-content:center;
                    color:#fff;">
        <div class="spinner-border text-light" role="status" style="width:3rem; height:3rem;">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-3">Preparing PDF...</p>
    </div> --}}


    <section class="invoice-section py-5">
        <div class="container-fluid p-4">
            <div class="card invoiceCard rounded p-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h2 class="h4 mb-1">Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h2>
                            <p class="text-muted mb-0">Issued on {{ $order->created_at?->toFormattedDateString() }}</p>
                        </div>
                        <button id="generate-pdf" class="btn btn-outline-danger bg-gradient-3"
                            style="position: relative; z-index: 10;">
                            <i class="fa fa-file-pdf-o mr-2"></i> Download PDF
                        </button>
                    </div>
                    <div class="row" id="invoice-container">

                        <!-- Left Content -->
                        <div class="col-lg-7">
                            <!-- Summary -->
                            <h5 class="mb-3">Summary</h5>
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <th scope="row" style="width:120px;">To</th>
                                        <td>{{ $client->name ?? ($order->buyer_name ?? '—') }}</td>
                                        <td>{{ $client->email ?? ($order->buyer_email ?? '—') }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">From</th>
                                        <td>{{ $order->brand->brand_name ?? '—' }}</td>
                                        <td>{{ config('mail.from.address') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Services -->
                            <h5 class="mt-4 mb-2">Services</h5>
                            <table class="table table-sm table-hover table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $order->service_name }}</td>
                                        <td class="text-right">{{ money_cents($order->unit_amount, $order->currency) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Payments -->
                            @if ($order->payments->count())
                                <h5 class="mt-4 mb-2">Payments</h5>
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->payments as $p)
                                            <tr>
                                                <td>{{ $p->created_at?->toDayDateTimeString() }}</td>
                                                <td class="text-right">{{ money_cents($p->amount, $p->currency) }}</td>
                                                <td><span
                                                        class="badge btn-sm badge-{{ $p->status == 'paid' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($p->status) }}
                                                    </span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                        <!-- Right Sidebar -->
                        <div class="col-lg-5">
                            <div class=" mb-3">
                                <div class="card-body">
                                    <h4 class="mb-0">{{ money_cents($order->unit_amount, $order->currency) }}</h4>
                                    <span class="badge badge-success mt-2">{{ ucfirst($order->status) }}</span>

                                    <div class="mt-3 small text-muted">
                                        Paid: {{ money_cents($order->amount_paid, $order->currency) }} <br>
                                        Due: {{ money_cents($order->balance_due, $order->currency) }}
                                    </div>
                                </div>
                            </div>

                            <div class="">
                                <div class="card-body">
                                    <p class="mb-2"><i class="fa fa-user mr-2"></i>
                                        {{ $client->name ?? ($order->buyer_name ?? '—') }}</p>
                                    <p class="mb-2"><i class="fa fa-calendar mr-2"></i>
                                        {{ $order->created_at?->toFormattedDateString() }}</p>
                                    <p class="mb-2"><i class="fa fa-folder mr-2"></i> {{ $order->service_name }}</p>

                                    @if (optional($order->paymentLinks->last())->last_issued_url)
                                        <p class="text-truncate mb-2">
                                            <i class="fa fa-link mr-2"></i>
                                            {{ optional($order->paymentLinks->last())->last_issued_url }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="pay-link">
                                @php
                                    $due = (int) ($order->balance_due ?? 0);
                                    $currency = $order->currency ?? 'USD';
                                @endphp
                                @if ($due > 0)
                                    @if (!empty($latestActiveLink))
                                        <small class="text-muted d-block mt-1">
                                            Outstanding balance: {{ number_format($due / 100, 2) }} {{ $currency }}
                                        </small>
                                    @else
                                        <div class="alert alert-warning mt-3 mb-0">
                                            An installment is due ({{ number_format($due / 100, 2) }}
                                            {{ $currency }}),
                                            but no active payment link is available yet.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-success mt-3 mb-0">
                                        This invoice is fully paid. Thank you!
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            const $downloadBtn = $('#generate-pdf');
            const $payLinks = $('.pay-link, .payment-btn, [data-role="paylink"]');
            // 👆 match your actual pay link buttons (adjust selector as needed)

            $('#generate-pdf').on('click', function() {
                // Hide buttons before screenshot
                $downloadBtn.hide();
                $payLinks.hide();
                const element = document.querySelector('.invoiceCard');
                const {
                    jsPDF
                } = window.jspdf;
                html2canvas(element, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        logging: false
                    }).then((canvas) => {
                        const imgData = canvas.toDataURL('image/png');
                        const pdf = new jsPDF('p', 'mm', 'a4');
                        // Keep proportions based on canvas size
                        const pageWidth = pdf.internal.pageSize.getWidth();
                        const imgHeight = (canvas.height * pageWidth) / canvas.width;
                        pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, imgHeight);
                        pdf.save(`invoice-{{ $order->id }}.pdf`);
                    })
                    .catch((err) => console.error('PDF Error:', err))
                    .finally(() => {
                        // Restore buttons after save
                        $downloadBtn.show();
                        $payLinks.show();
                        $loader.hide();
                    });
            });
        });
    </script>




@endsection
