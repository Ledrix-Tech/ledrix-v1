@extends('sellers.layout.layout')

@section('title', 'Seller | Leaderboard')

@section('sellers-content')
    <div class="crm-page-header">
        <div>
            <h1>Seller Leaderboard</h1>
            <p>
                Rankings for {{ $brandName ?? 'your brand' }} — conversion, revenue, response time, and risk score.
            </p>
        </div>
        <div class="crm-page-actions">
            <a href="{{ route('seller.sellers.get') }}" class="btn btn-sm btn-crm-outline">
                <i class="bi bi-person-badge me-1"></i> All sellers
            </a>
        </div>
    </div>

    @if ($ranked->isNotEmpty())
        <div class="crm-dash-kpis mb-4">
            @php $top = $ranked->first(); @endphp
            <div class="crm-dash-kpi">
                <div class="crm-dash-kpi-top">
                    <div>
                        <div class="crm-dash-kpi-label">Top performer</div>
                        <div class="crm-dash-kpi-value" style="font-size: 1.25rem;">{{ $top['name'] }}</div>
                    </div>
                    <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                        <i class="bi bi-trophy"></i>
                    </div>
                </div>
                <div class="crm-dash-kpi-meta"><span>Score {{ number_format($top['final_score'], 1) }}</span></div>
            </div>
            <div class="crm-dash-kpi">
                <div class="crm-dash-kpi-top">
                    <div>
                        <div class="crm-dash-kpi-label">Active sellers</div>
                        <div class="crm-dash-kpi-value">{{ $ranked->count() }}</div>
                    </div>
                    <div class="crm-dash-kpi-icon crm-dash-kpi-icon--blue">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="crm-dash-kpi-meta"><span>In this brand</span></div>
            </div>
            <div class="crm-dash-kpi">
                <div class="crm-dash-kpi-top">
                    <div>
                        <div class="crm-dash-kpi-label">Combined revenue</div>
                        <div class="crm-dash-kpi-value">{{ money_cents((int) ($ranked->sum('revenue') * 100)) }}</div>
                    </div>
                    <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="crm-dash-kpi-meta"><span>Paid orders</span></div>
            </div>
        </div>
    @endif

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Seller</th>
                            <th>Role</th>
                            <th>Conversion</th>
                            <th>Revenue</th>
                            <th>Avg response</th>
                            <th>Risk score</th>
                            <th>Final score</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ranked as $row)
                            @php
                                $isMe = auth('seller')->id() === ($row['seller_id'] ?? null);
                                $rankClass = match ($row['rank']) {
                                    1 => 'crm-status-success',
                                    2 => 'crm-status-info',
                                    3 => 'crm-status-warning',
                                    default => 'crm-status-neutral',
                                };
                            @endphp
                            <tr @class(['table-active' => $isMe])>
                                <td data-label="Rank">
                                    <span class="crm-status {{ $rankClass }}">
                                        @if ($row['rank'] === 1)
                                            <i class="bi bi-trophy-fill"></i>
                                        @endif
                                        #{{ $row['rank'] }}
                                    </span>
                                </td>
                                <td data-label="Seller">
                                    <div class="fw-semibold">
                                        {{ $row['name'] }}
                                        @if ($isMe)
                                            <span class="badge bg-secondary ms-1">You</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Role">{{ $row['role'] }}</td>
                                <td data-label="Conversion">{{ number_format($row['conversion'], 1) }}%</td>
                                <td data-label="Revenue">{{ money_cents((int) ($row['revenue'] * 100)) }}</td>
                                <td data-label="Response">{{ $row['response'] }}</td>
                                <td data-label="Risk">{{ number_format($row['churn_score'], 1) }}</td>
                                <td data-label="Score">
                                    <strong>{{ number_format($row['final_score'], 1) }}</strong>
                                </td>
                                <td class="text-end" data-label="Actions">
                                    <a href="{{ route('seller.seller-performance.get', $row['seller_id']) }}"
                                        class="btn btn-sm btn-crm-outline">
                                        <i class="bi bi-graph-up-arrow"></i> Performance
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="crm-empty py-5">
                                        <i class="bi bi-trophy d-block"></i>
                                        No active sellers to rank yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Score formula: 40% conversion + 40% revenue − 20% client risk. Only active sellers in your brand are included.
    </p>
@endsection
