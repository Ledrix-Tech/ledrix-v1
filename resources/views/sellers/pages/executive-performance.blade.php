@extends('sellers.layout.layout')

@section('title', 'Seller | Performance')

@push('styles')
    <link rel="stylesheet" href="{{ asset('seller-assets/css/dashboard.css') }}">
@endpush

@section('sellers-content')
    @php
        $growth = $performance['monthly_growth'] ?? null;
        $bonusEarned = (float) ($performance['bonus_earned'] ?? 0);
    @endphp

    <div class="crm-page-header">
        <div>
            <h1>{{ $seller->name ?? 'Performance' }}</h1>
            <p>
                {{ Str::headline($seller->is_seller ?? 'seller') }}
                @if ($seller->brand?->brand_name)
                    · {{ $seller->brand->brand_name }}
                @endif
            </p>
        </div>
        <div class="crm-page-actions">
            @if (isProjectManager() || isFrontSeller())
                <a href="{{ route('seller.leads.get') }}" class="btn btn-sm btn-crm-teal">
                    <i class="bi bi-funnel me-1"></i> View leads
                </a>
            @endif
            @if (isProjectManager())
                <a href="{{ route('seller.assigned-leads.get') }}" class="btn btn-sm btn-crm-outline">
                    <i class="bi bi-basket me-1"></i> Assigned leads
                </a>
            @else
                <a href="{{ route('seller.orders.get') }}" class="btn btn-sm btn-crm-outline">
                    <i class="bi bi-receipt me-1"></i> Orders
                </a>
            @endif
        </div>
    </div>

    <div class="crm-dash-kpis">
        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Net revenue</div>
                    <div class="crm-dash-kpi-value">{{ money_cents((int) (($performance['net_revenue'] ?? 0) * 100)) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>After refunds & chargebacks</span></div>
        </div>

        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Gross revenue</div>
                    <div class="crm-dash-kpi-value">{{ money_cents((int) (($performance['gross_revenue'] ?? 0) * 100)) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--blue">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Before refunds</span></div>
        </div>

        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Refunds</div>
                    <div class="crm-dash-kpi-value text-danger">
                        -{{ money_cents((int) (($performance['refunds'] ?? 0) * 100)) }}
                    </div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--rose">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
            </div>
        </div>

        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Chargebacks</div>
                    <div class="crm-dash-kpi-value text-danger">
                        -{{ money_cents((int) (($performance['chargebacks'] ?? 0) * 100)) }}
                    </div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--rose">
                    <i class="bi bi-shield-x"></i>
                </div>
            </div>
        </div>

        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Conversion</div>
                    <div class="crm-dash-kpi-value">{{ $performance['conversion_rate'] ?? 0 }}%</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                    <i class="bi bi-percent"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>{{ $performance['total_leads'] ?? 0 }} leads</span>
            </div>
        </div>

        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Performance bonus</div>
                    <div class="crm-dash-kpi-value">
                        @if ($bonusEarned > 0)
                            +{{ number_format($bonusEarned, 2) }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--purple">
                    <i class="bi bi-trophy"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Target {{ number_format($performance['bonus_rule_target'] ?? 0, 2) }}</span>
                <span>Bonus {{ number_format($performance['bonus_rule_amount'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="crm-dash-panels">
        <div class="crm-dash-card">
            <div class="card-body">
                <div class="crm-card-header">
                    <div>
                        <h6 class="crm-dash-card-title">Lead breakdown</h6>
                        <span class="crm-dash-card-sub">{{ $performance['total_leads'] ?? 0 }} total leads</span>
                    </div>
                </div>
                @forelse($leadStatuses as $status => $count)
                    <div class="crm-dash-row">
                        <div class="crm-brand-badge">{{ strtoupper(substr($status, 0, 2)) }}</div>
                        <div class="crm-dash-row-info">
                            <div class="crm-dash-row-name">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                            <div class="crm-dash-row-sub">{{ $count }} {{ Str::plural('lead', $count) }}</div>
                        </div>
                        <span class="crm-status-pill crm-status-pill--active">{{ $count }}</span>
                    </div>
                @empty
                    <div class="crm-empty">
                        <i class="bi bi-funnel"></i>
                        No leads found for this seller
                    </div>
                @endforelse
            </div>
        </div>

        <div class="crm-dash-card">
            <div class="card-body">
                <div class="crm-card-header">
                    <div>
                        <h6 class="crm-dash-card-title">Quick stats</h6>
                        <span class="crm-dash-card-sub">Orders & growth</span>
                    </div>
                </div>
                <div class="crm-dash-row">
                    <div class="crm-dash-row-info">
                        <div class="crm-dash-row-name">Avg order value</div>
                    </div>
                    <div class="crm-merch-amt">
                        {{ money_cents((int) (($performance['avg_order_value'] ?? 0) * 100)) }}
                    </div>
                </div>
                <div class="crm-dash-row">
                    <div class="crm-dash-row-info">
                        <div class="crm-dash-row-name">Monthly growth</div>
                    </div>
                    <div class="crm-merch-amt {{ !is_null($growth) && $growth < 0 ? 'text-danger' : '' }}">
                        @if (!is_null($growth))
                            {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="crm-dash-row">
                    <div class="crm-dash-row-info">
                        <div class="crm-dash-row-name">Paid orders</div>
                    </div>
                    <div class="crm-merch-amt">{{ $performance['paid_orders'] ?? 0 }}</div>
                </div>
                <div class="crm-dash-row">
                    <div class="crm-dash-row-info">
                        <div class="crm-dash-row-name">Outstanding due</div>
                    </div>
                    <div class="crm-merch-amt">{{ money_cents((int) (($performance['total_due'] ?? 0) * 100)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-dash-chart-card mb-4">
        <div class="crm-dash-chart-head">
            <div>
                <h2>Monthly performance</h2>
                <p>Net credited revenue by month</p>
            </div>
            <span class="crm-dash-badge">
                <i class="bi bi-graph-up"></i> {{ count($months ?? []) }} months
            </span>
        </div>
        <div class="crm-dash-chart-body">
            @if (!empty($months) && count($months) > 0)
                <canvas id="monthlyIncomeChart"
                    data-months='@json($months ?? [])'
                    data-totals='@json($totals ?? [])'></canvas>
            @else
                <div class="crm-dash-logs-empty">
                    <i class="bi bi-bar-chart"></i>
                    No payment history yet — chart will appear once revenue is recorded.
                </div>
            @endif
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-header px-3 pt-3">
            <h2 class="h6 mb-0">Orders by client</h2>
        </div>
        <div class="crm-card-body p-0">
            @if ($clientsWithOrders->isEmpty())
                <div class="crm-empty m-3">
                    <i class="bi bi-people"></i>
                    No client orders credited to this seller yet.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Orders</th>
                                <th>Last payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clientsWithOrders as $row)
                                @php($client = $row['client'])
                                <tr>
                                    <td data-label="Client">
                                        <div class="fw-semibold">{{ $client?->name ?? '—' }}</div>
                                        <div class="text-muted small">{{ $client?->email }}</div>
                                    </td>
                                    <td data-label="Orders">
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($row['orders'] as $order)
                                                <li class="mb-1">
                                                    <strong>#{{ $order->id }}</strong> — {{ $order->service_name }}
                                                    <span class="text-muted small">
                                                        ({{ money_cents((int) $order->payments->sum('amount')) }})
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td data-label="Last payment">
                                        @if ($row['last_payment'])
                                            {{ money_cents((int) $row['last_payment']->amount) }}
                                            <div class="text-muted small">
                                                {{ $row['last_payment']->created_at->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-muted">No credited payments</span>
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
@endsection

@push('scripts')
    @if (!empty($months) && count($months) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script src="{{ asset('seller-assets/js/performance.js') }}"></script>
    @endif
@endpush
