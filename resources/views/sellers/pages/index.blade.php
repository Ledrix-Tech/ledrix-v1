@extends('sellers.layout.layout')

@section('title', 'Seller | Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('seller-assets/css/dashboard.css') }}">
@endpush

@section('sellers-content')
    @php
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp

    <div class="crm-dash-hero">
        <div class="crm-dash-hero-inner">
            <div class="crm-dash-hero-text">
                <h1>{{ $greeting }}{{ $seller?->name ? ', ' . explode(' ', $seller->name)[0] : '' }}</h1>
                <p>Your sales workspace — pipeline, orders, and live lead activity.</p>
            </div>
            <div class="crm-dash-hero-meta">
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-calendar3"></i>
                    {{ now()->format('l, M j, Y') }}
                </span>
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-building"></i>
                    {{ $seller->brand?->brand_name ?? 'Your brand' }}
                </span>
                @if ($tenantHasApiAccess ?? false)
                    <a href="{{ route('seller.domain-script.get') }}" class="crm-dash-hero-chip text-decoration-none text-white">
                        <i class="bi bi-code-slash"></i>
                        Get script
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="crm-dash-kpis">
        <a href="{{ route('seller.brands.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Brands</div>
                    <div class="crm-dash-kpi-value">{{ number_format($brands ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Active workspaces</span></div>
        </a>

        <a href="{{ route('seller.leads.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Leads</div>
                    <div class="crm-dash-kpi-value">{{ number_format($leads ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Your brand pipeline</span></div>
        </a>

        <a href="{{ route('seller.orders.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Orders</div>
                    <div class="crm-dash-kpi-value">{{ number_format($orders ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--amber">
                    <i class="bi bi-cart-check"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Total orders placed</span></div>
        </a>

        @if ($tenantHasPayments ?? false)
            <a href="{{ route('seller.payments.get') }}" class="crm-dash-kpi">
                <div class="crm-dash-kpi-top">
                    <div>
                        <div class="crm-dash-kpi-label">Payments</div>
                        <div class="crm-dash-kpi-value">{{ number_format($payments ?? 0) }}</div>
                    </div>
                    <div class="crm-dash-kpi-icon crm-dash-kpi-icon--rose">
                        <i class="bi bi-credit-card"></i>
                    </div>
                </div>
                <div class="crm-dash-kpi-meta"><span>Payment records</span></div>
            </a>

            <a href="{{ route('seller.payments.get') }}" class="crm-dash-kpi">
                <div class="crm-dash-kpi-top">
                    <div>
                        <div class="crm-dash-kpi-label">Revenue</div>
                        <div class="crm-dash-kpi-value">{{ money_cents((int) ($revenue ?? 0)) }}</div>
                    </div>
                    <div class="crm-dash-kpi-icon crm-dash-kpi-icon--blue">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <div class="crm-dash-kpi-meta">
                    <span>Paid <strong>{{ money_cents((int) ($paymentPaid ?? 0)) }}</strong></span>
                    <span class="due">Due <strong>{{ money_cents((int) ($paymentDue ?? 0)) }}</strong></span>
                </div>
            </a>
        @endif
    </div>

    @if (!empty($months) && count($months) > 0)
        <div class="crm-dash-chart-card">
            <div class="crm-dash-chart-head">
                <div>
                    <h2>Revenue trend</h2>
                    <p>Monthly paid order income for your brand</p>
                </div>
                <span class="crm-dash-badge">
                    <i class="bi bi-graph-up-arrow"></i> {{ count($months) }} months tracked
                </span>
            </div>
            <div class="crm-dash-chart-body">
                <canvas id="orderPaymentsChart"
                    data-months='@json($months ?? [])'
                    data-totals='@json($totals ?? [])'></canvas>
            </div>
        </div>
    @endif

    <div class="crm-dash-logs">
        <div class="crm-dash-logs-head">
            <h2><i class="bi bi-activity"></i> Lead view activity</h2>
        </div>
        <div class="crm-dash-logs-body">
            @if (!empty($logs) && count($logs) > 0)
                @foreach ($logs as $log)
                    <div class="crm-log-line">
                        <span class="crm-log-dot"></span>
                        <span class="crm-log-text">{{ $log }}</span>
                    </div>
                @endforeach
            @else
                <div class="crm-dash-logs-empty">
                    <i class="bi bi-journal-text"></i>
                    No lead view logs yet — activity will appear here in real time.
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($months) && count($months) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script src="{{ asset('seller-assets/js/dashboard.js') }}"></script>
    @endif
@endpush
