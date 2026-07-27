@extends('admin.layout.layout')

@section('title', 'Admin | Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/dashboard.css') }}">
@endpush

@section('admin-content')
    @php
        $adminUser = auth('admin')->user();
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp

    {{-- Hero --}}
    <div class="crm-dash-hero">
        <div class="crm-dash-hero-inner">
            <div class="crm-dash-hero-text">
                <h1>{{ $greeting }}{{ $adminUser?->name ? ', ' . explode(' ', $adminUser->name)[0] : '' }}</h1>
                <p>Your workspace at a glance — leads, revenue, and live activity.</p>
            </div>
            <div class="crm-dash-hero-meta">
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-calendar3"></i>
                    {{ now()->format('l, M j, Y') }}
                </span>
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-building"></i>
                    {{ $brands->count() }} {{ Str::plural('brand', $brands->count()) }}
                </span>
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-lightning-charge"></i>
                    {{ $leads ?? 0 }} leads total
                </span>
            </div>
        </div>
    </div>

    {{-- Brand filter --}}
    <div class="crm-filter-pills">
        @foreach ($brands as $brand)
            <span class="crm-pill {{ request('id') == $brand->id ? 'active' : '' }}"
                onclick="window.location='{{ request()->fullUrlWithQuery(['id' => $brand->id]) }}'">
                <span class="crm-pill-dot" style="background:#04be63"></span>
                {{ $brand->brand_name }}
            </span>
        @endforeach
        <a href="{{ route('admin.index.get') }}" class="crm-pill">
            <i class="bi bi-grid-3x3-gap" style="font-size:11px"></i> All brands
        </a>
    </div>

    {{-- KPI cards --}}
    <div class="crm-dash-kpis">
        <a href="{{ route('admin.brand-payments.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">PPC Revenue</div>
                    <div class="crm-dash-kpi-value">{{ money_cents($revenue ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--blue">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Paid <strong>{{ money_cents($paymentPaid ?? 0) }}</strong></span>
                <span class="due">Due <strong>{{ money_cents($paymentDue ?? 0) }}</strong></span>
            </div>
        </a>

        <a href="{{ route('admin.brand-payments.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Upwork Revenue</div>
                    <div class="crm-dash-kpi-value">{{ money_cents($upworkRevenue ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--purple">
                    <i class="bi bi-briefcase"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Paid <strong>{{ money_cents($upworkPaymentPaid ?? 0) }}</strong></span>
                <span class="due">Due <strong>{{ money_cents($upworkPaymentDue ?? 0) }}</strong></span>
            </div>
        </a>

        <a href="{{ route('admin.brands.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Brands</div>
                    <div class="crm-dash-kpi-value">{{ $brands->count() }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Active LLC workspaces</span>
            </div>
        </a>

        <a href="{{ route('admin.leads.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Leads</div>
                    <div class="crm-dash-kpi-value">{{ number_format($leads ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>All-time pipeline</span>
            </div>
        </a>

        <a href="{{ route('admin.orders.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Orders</div>
                    <div class="crm-dash-kpi-value">{{ number_format($orders ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--amber">
                    <i class="bi bi-cart-check"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Total orders placed</span>
            </div>
        </a>

        <a href="{{ route('admin.orders.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Payments</div>
                    <div class="crm-dash-kpi-value">{{ number_format($payments ?? 0) }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--rose">
                    <i class="bi bi-credit-card"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta">
                <span>Payment records</span>
            </div>
        </a>
    </div>

    {{-- Revenue chart --}}
    <div class="crm-dash-chart-card">
        <div class="crm-dash-chart-head">
            <div>
                <h2>Revenue trend</h2>
                <p>Monthly paid order income across all brands</p>
            </div>
            <span class="crm-dash-badge">
                <i class="bi bi-graph-up-arrow"></i> {{ count($months ?? []) }} months tracked
            </span>
        </div>
        <div class="crm-dash-chart-body">
            <canvas id="orderPaymentsChart"
                data-months='@json($months ?? [])'
                data-totals='@json($totals ?? [])'></canvas>
        </div>
    </div>

    {{-- Recent activity panels --}}
    <div class="crm-dash-panels">
        <div class="crm-dash-card">
            <div class="card-body">
                <div class="crm-card-header">
                    <div>
                        <h6 class="crm-dash-card-title">Recent leads</h6>
                        <span class="crm-dash-card-sub">Latest 5 across all brands</span>
                    </div>
                    <a href="{{ route('admin.leads.get') }}" class="crm-dash-link">View all →</a>
                </div>
                @forelse($recentLeads as $lead)
                    @php
                        $parts = preg_split('/\s+/', trim($lead->name ?? ''));
                        $initials = strtoupper(substr($parts[0] ?? '?', 0, 1));
                        if (count($parts) > 1) {
                            $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
                        }
                        $statusClass = match ($lead->status) {
                            'new' => 'crm-status-pill--new',
                            'client' => 'crm-status-pill--won',
                            'disqualified' => 'crm-status-pill--lost',
                            default => 'crm-status-pill--active',
                        };
                    @endphp
                    <div class="crm-dash-row">
                        <div class="crm-avatar" style="background:rgba(68,56,201,0.12);color:var(--crm-blue);">
                            {{ $initials }}
                        </div>
                        <div class="crm-dash-row-info">
                            <div class="crm-dash-row-name">{{ $lead->name }}</div>
                            <div class="crm-dash-row-sub">
                                {{ $lead->brand?->brand_name ?? '—' }}
                                · {{ $lead->service ?? ($lead->domain_url ?? 'Direct') }}
                            </div>
                        </div>
                        <span class="crm-status-pill {{ $statusClass }}">{{ $lead->status }}</span>
                    </div>
                @empty
                    <div class="crm-empty">
                        <i class="bi bi-inbox"></i>
                        No recent leads yet
                    </div>
                @endforelse
            </div>
        </div>

        <div class="crm-dash-card">
            <div class="card-body">
                <div class="crm-card-header">
                    <div>
                        <h6 class="crm-dash-card-title">Brand revenue</h6>
                        <span class="crm-dash-card-sub">Paid totals & chargebacks</span>
                    </div>
                    <a href="{{ route('admin.account-keys.get') }}" class="crm-dash-link">Manage →</a>
                </div>
                @forelse($merchants as $merchant)
                    @php
                        $brandInitials = strtoupper(substr($merchant->brand_name ?? 'BR', 0, 2));
                        $revenueCents = (int) ($merchant->total_revenue ?? 0);
                        $cbCents = (int) ($merchant->chargebacks ?? 0);
                    @endphp
                    <div class="crm-dash-row">
                        <div class="crm-brand-badge">{{ $brandInitials }}</div>
                        <div class="crm-dash-row-info">
                            <div class="crm-dash-row-name">{{ $merchant->brand_name }}</div>
                            <div class="crm-dash-row-sub">
                                {{ $merchant->orders_count ?? 0 }} {{ Str::plural('order', $merchant->orders_count ?? 0) }}
                            </div>
                        </div>
                        <div>
                            <div class="crm-merch-amt">{{ money_cents($revenueCents) }}</div>
                            @if ($cbCents > 0)
                                <div class="crm-merch-cb">−{{ money_cents($cbCents) }} CB</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="crm-empty">
                        <i class="bi bi-shop"></i>
                        No brand payment data yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Lead view logs --}}
    <div class="crm-dash-logs">
        <div class="crm-dash-logs-head">
            <h2><i class="bi bi-activity"></i> Lead view activity</h2>
            <form method="POST" action="{{ route('admin.lead.logs.clear') }}"
                onsubmit="return confirm('Clear all lead view logs?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-crm-outline">
                    <i class="bi bi-trash3"></i> Clear logs
                </button>
            </form>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('admin-assets/js/dashboard.js') }}"></script>
@endpush
