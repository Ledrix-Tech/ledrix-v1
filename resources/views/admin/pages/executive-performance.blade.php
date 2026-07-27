@extends('admin.layout.layout')

@section('title', 'Admin | Seller Performance')

@section('admin-content')

    <div class=" justify-content-between align-items-start">
        <h1 class="fw-bold" style="color: #003C51;">{{ $seller->name ?? 'N/A' }}</h1>
        <span style="color: red !important; font-weight: bold;">{{ Str::headline($seller->is_seller) ?? 'N/A' }}</span>
    </div>


    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <hr>
                <div class="tab-content tab-transparent-content">
                    <div class="tab-pane fade show active" id="business-1" role="tabpanel" aria-labelledby="business-tab">
                        <div class="row my-5">
                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Net Revenue</h5>
                                        <h2 class="mb-0 font-weight-bold text-success">
                                            {{ money_cents(($performance['net_revenue'] ?? 0) * 100) }}
                                        </h2>
                                        <small class="text-muted">After refunds & chargebacks</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Gross Revenue</h5>
                                        <h2 class="mb-0 font-weight-bold text-dark">
                                            {{ money_cents(($performance['gross_revenue'] ?? 0) * 100) }}
                                        </h2>
                                        <small class="text-muted">Before refunds</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Refunds</h5>
                                        <h2 class="mb-0 font-weight-bold text-danger">
                                            -{{ money_cents(($performance['refunds'] ?? 0) * 100) }}
                                        </h2>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Chargebacks</h5>
                                        <h2 class="mb-0 font-weight-bold text-danger">
                                            -{{ money_cents(($performance['chargebacks'] ?? 0) * 100) }}
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row my-4">
                            <div class="col-xl-4 col-lg-4 col-sm-4 grid-margin stretch-card">
                                <div class="card">
                                    <a href="javascript:void(0);" style="text-decoration:none;">
                                        <div class="card-body text-center">
                                            <h5 class="mb-2 text-danger font-weight-normal py-2">Conversion Rate</h5>
                                            <h2 class="mb-0 font-weight-bold text-dark">
                                                {{ $performance['conversion_rate'] ?? '-' }} %
                                            </h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="mb-2 text-danger font-weight-normal py-2">Performance Bonus</h5>

                                        <h2 class="font-weight-bold text-success mb-1">
                                            @if ($performance['bonus_earned'] > 0)
                                                +{{ number_format($performance['bonus_earned'], 2) }}
                                                {{ $currency ?? 'USD' }}
                                            @else
                                                —
                                            @endif
                                        </h2>

                                        <small class="text-muted">
                                            Target: {{ number_format($performance['bonus_rule_target'] ?? 0, 2) }}
                                            |
                                            Bonus: {{ number_format($performance['bonus_rule_amount'] ?? 0, 2) }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <a href="javascript:void(0);" style="text-decoration:none;">
                                        <div class="card-body text-center">
                                            <h5 class="mb-2 text-danger font-weight-normal py-2">Avg Order Value</h5>
                                            <h2 class="mb-0 font-weight-bold text-dark">
                                                {{ money_cents(($performance['avg_order_value'] ?? 0) * 100) }}
                                            </h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            @php $growth = $performance['monthly_growth'] ?? null; @endphp
                            <div class="col-xl-2 col-lg-2 col-sm-2 grid-margin stretch-card">
                                <div class="card">
                                    <a href="javascript:void(0);" style="text-decoration:none;">
                                        <div class="card-body text-center">
                                            <h5 class="mb-2 text-danger font-weight-normal py-2">Monthly Growth</h5>
                                            <h2
                                                class="mb-0 font-weight-bold {{ $growth >= 0 ? 'text-success' : 'text-danger' }}">
                                                @if (!is_null($growth))
                                                    {{ $growth >= 0 ? '+' : '' }}{{ $growth }} %
                                                @else
                                                    —
                                                @endif
                                            </h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        {{-- Forecast Revenue Cards for Sellers --}}
                        <div class="row my-4">
                            @foreach ($sellerForecasts as $sellerId => $forecast)
                                <div class="col-md-6 col-lg-6 mb-4 mx-auto">
                                    <div class="card shadow-sm border-success">
                                        <div class="card-body text-center">
                                            <h5 class="mb-2 text-danger font-weight-normal py-2">Forecast Revenue:</h5>
                                            <h2
                                                class="mb-0 font-weight-bold {{ money_cents($forecast) >= 0 ? 'text-success' : 'text-danger' }}">
                                                @if (!is_null(money_cents($forecast)))
                                                    {{ money_cents($forecast) >= 0 ? '+' : '' }}{{ money_cents($forecast) }}
                                                @else
                                                    —
                                                @endif
                                            </h2>
                                            <small class="text-muted">Forecasted revenue for the next period</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="row my-5">
                            <div class="col-xl-3 col-lg-3 col-sm-3 grid-margin stretch-card">
                                <div class="card">
                                    <a href="javascript:void(0);" style="text-decoration:none;">
                                        <div class="card-body text-center">
                                            <h5 class="mb-2 text-danger font-weight-normal py-2">Total Leads</h5>
                                            <div
                                                class="dashboard-progress dashboard-progress-3 d-flex align-items-center justify-content-center item-parent my-4">
                                                <img src="https://cdn-icons-png.flaticon.com/128/2275/2275248.png"
                                                    alt="" style="width: 60px;">
                                            </div>
                                            <h2 class="mb-0 font-weight-bold text-dark">
                                                {{ $performance['total_leads'] }}
                                            </h2>
                                            <ul class="list-unstyled mt-3">
                                                @forelse($leadStatuses as $status => $count)
                                                    <li class="fw-bold">
                                                        <span
                                                            class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $status)) }}
                                                            - {{ $count }}</span>
                                                    </li>
                                                @empty
                                                    <li class="text-muted">No leads found</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="col-xl-9 col-lg-9 col-sm-9 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="mb-2 text-danger font-weight-normal">Monthly Performance</h5>
                                        <canvas id="monthlyIncomeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="mb-3 text-danger font-weight-bold"> Orders By Client
                                        </h5>
                                        @if ($clientsWithOrders->isEmpty())
                                            <p class="text-muted">No clients found for this seller 🎉</p>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Client</th>
                                                            <th>Orders (Services)</th>
                                                            <th>Payments</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($clientsWithOrders as $row)
                                                            @php($client = $row['client'])
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $client?->name ?? '—' }}</strong><br>
                                                                    <small
                                                                        class="text-muted">{{ $client?->email }}</small>
                                                                </td>
                                                                <td>
                                                                    <ul class="list-unstyled mb-0">
                                                                        @foreach ($row['orders'] as $order)
                                                                            <li>
                                                                                <strong>#{{ $order->id }}</strong> –
                                                                                {{ $order->service_name }}
                                                                                <small class="text-muted">
                                                                                    Paid to this seller:
                                                                                    {{ money_cents($order->payments->sum('amount')) }}
                                                                                </small>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </td>
                                                                <td>
                                                                    @if ($row['last_payment'])
                                                                        {{ money_cents($row['last_payment']->amount) }}
                                                                        <br><small>{{ $row['last_payment']->created_at->diffForHumans() }}</small>
                                                                    @else
                                                                        <span class="text-muted">No credited
                                                                            payments</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxOrder = document.getElementById('monthlyIncomeChart').getContext('2d');

        // Gradient fill
        let gradient = ctxOrder.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

        new Chart(ctxOrder, {
            type: 'line',
            data: {
                labels: @json($months), // e.g. ["2025-01","2025-02","2025-03"]
                datasets: [{
                    label: 'Payments ($)',
                    data: @json($totals), // e.g. [1200, 800, 1500]
                    fill: true,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: gradient,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(54, 162, 235, 1)',
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>




@endsection
