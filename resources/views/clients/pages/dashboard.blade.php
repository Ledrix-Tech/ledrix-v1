@extends('clients.layouts.layout')

@section('title', 'Dashboard | Client Portal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('seller-assets/css/dashboard.css') }}">
@endpush

@section('client-content')
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
                <h1>{{ $greeting }}{{ $client->name ? ', ' . explode(' ', trim($client->name))[0] : '' }}</h1>
                <p>Your client workspace — projects, briefs, invoices, and support.</p>
            </div>
            <div class="crm-dash-hero-meta">
                <span class="crm-dash-hero-chip">
                    <i class="bi bi-calendar3"></i>
                    {{ now()->format('l, M j, Y') }}
                </span>
            </div>
        </div>
    </div>

    <div class="crm-dash-kpis">
        <a href="{{ route('client.raised-tickets.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Tickets</div>
                    <div class="crm-dash-kpi-value"><i class="bi bi-life-preserver"></i></div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                    <i class="bi bi-headset"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>View support tickets</span></div>
        </a>

        <a href="{{ route('client.brief.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Briefs</div>
                    <div class="crm-dash-kpi-value"><i class="bi bi-journal-text"></i></div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-pencil-square"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Complete project questionnaires</span></div>
        </a>

        <a href="{{ route('client.invoice.get') }}" class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Invoices</div>
                    <div class="crm-dash-kpi-value"><i class="bi bi-receipt"></i></div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--amber">
                    <i class="bi bi-credit-card"></i>
                </div>
            </div>
            <div class="crm-dash-kpi-meta"><span>Pay and track invoices</span></div>
        </a>
    </div>

    <div class="crm-card">
        <div class="crm-card-header">
            <h2>Service progress</h2>
        </div>
        <div class="crm-card-body">
            <canvas id="orderStatusChart" style="max-height: 320px; width: 100%;"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const progressData = @json($chartData);
            const canvas = document.getElementById('orderStatusChart');
            if (!canvas || typeof Chart === 'undefined') return;

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: progressData.map(p => p.status),
                    datasets: [{
                        label: 'Orders',
                        data: progressData.map(p => p.count),
                        borderRadius: 8,
                        backgroundColor: ['#4438c9', '#8b52fe', '#04be63', '#f59e0b', '#94a3b8', '#0ea5e9', '#ef4444', '#64748b'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>
@endpush
