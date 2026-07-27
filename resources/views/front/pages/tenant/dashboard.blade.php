@extends('front.layout.layout')

@section('title', 'Workspace Dashboard')

@section('robots', 'noindex, nofollow')

@section('main-content')
    <header class="hero d-flex align-items-center justify-content-center text-center"
        style="background: linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.5)), url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center; background-size: cover; min-height: 220px;">
        <div class="container text-white">
            <h1>{{ $tenant->name }}</h1>
            <p class="mb-0">{{ $tenant->slug }}.ledrix.app · {{ $tenant->email }}</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h4 class="mb-0">Workspace Overview</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('tenant.billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
                    <form method="POST" action="{{ route('tenant.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Sign out</button>
                    </form>
                </div>
            </div>

            @if ($needsPayment)
                <div class="alert alert-warning">
                    Your subscription has expired or payment is pending.
                    <a href="{{ route('tenant.billing') }}" class="alert-link">Renew your subscription</a>
                    to restore CRM access.
                </div>
            @elseif ($expiresSoon)
                <div class="alert alert-info">
                    Subscription renews in {{ $daysUntilRenewal }} day(s)
                    @if ($membership?->end_date)
                        ({{ $membership->end_date->format('M d, Y') }})
                    @endif.
                    <a href="{{ route('tenant.billing') }}" class="alert-link">Renew early</a>
                </div>
            @elseif ($tenant->isOnTrial())
                <div class="alert alert-info">
                    Free trial active — {{ $tenant->trialDaysLeft() }} day(s) left.
                    <a href="{{ route('tenant.billing') }}" class="alert-link">View billing</a>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-dark text-white">Company</div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Status:</strong>
                                <span class="badge bg-{{ $tenant->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </p>
                            <p class="mb-2"><strong>Country:</strong> {{ $tenant->country ?? 'N/A' }}</p>
                            <p class="mb-2"><strong>Website:</strong> {{ $tenant->website ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Joined:</strong> {{ $tenant->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">Subscription</div>
                        <div class="card-body">
                            @if ($membership)
                                <p class="mb-2"><strong>Plan:</strong> {{ $plan?->name ?? 'N/A' }}</p>
                                <p class="mb-2"><strong>Cycle:</strong> {{ ucfirst($membership->billing_cycle) }}</p>
                                <p class="mb-2"><strong>Status:</strong>
                                    <span class="badge bg-info text-dark">{{ ucfirst(str_replace('_', ' ', $membership->status)) }}</span>
                                </p>
                                @if ($membership->isOnTrial())
                                    <p class="mb-2"><strong>Trial ends:</strong> {{ $membership->trial_end?->format('M d, Y') }}</p>
                                    <p class="mb-0"><strong>Days left:</strong> {{ $tenant->trialDaysLeft() }}</p>
                                @elseif ($membership->end_date)
                                    <p class="mb-0"><strong>Renews / ends:</strong> {{ $membership->end_date->format('M d, Y') }}</p>
                                @endif
                            @else
                                <p class="text-muted mb-0">No active membership found.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-success text-white">Plan Limits</div>
                        <div class="card-body">
                            @if ($plan)
                                <ul class="list-unstyled small mb-3">
                                    @foreach ($limits as $key => $value)
                                        <li class="d-flex justify-content-between border-bottom py-1">
                                            <span>{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                            <strong>{{ $value == -1 ? 'Unlimited' : $value }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($membership?->api_key)
                                    <p class="small mb-0"><strong>API Key:</strong><br>
                                        <code class="small">{{ \Illuminate\Support\Str::limit($membership->api_key, 20, '...') }}</code>
                                    </p>
                                @endif
                            @else
                                <p class="text-muted mb-0">No plan assigned.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Billing & Invoices</span>
                    <a href="{{ route('tenant.billing') }}" class="btn btn-sm btn-outline-primary">Manage billing</a>
                </div>
                <div class="card-body">
                    @include('front.pages.tenant.partials.invoice-table', ['invoices' => $invoices])
                </div>
            </div>

            {{-- CRM access — primary DB panel (separate from this billing dashboard) --}}
            <div class="card shadow-sm mt-4 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span>CRM Workspace</span>
                    @if ($canUseCrm)
                        <span class="badge bg-light text-primary">Access enabled</span>
                    @else
                        <span class="badge bg-warning text-dark">Payment required</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        This is your <strong>subscription dashboard</strong> (central database).
                        Click below to enter the CRM — your admin account will be created automatically
                        and you'll be signed in with one click.
                    </p>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <ul class="small text-muted mb-0">
                                <li>Workspace slug: <strong>{{ $tenant->slug }}</strong></li>
                                <li>CRM panel: leads, sellers, brands, orders, payments</li>
                                <li>Data is isolated to your tenant account</li>
                            </ul>
                        </div>
                        <div class="col-md-4 d-flex align-items-center justify-content-md-end">
                            @if ($canUseCrm)
                                <a href="{{ route('tenant.goto-crm') }}" class="btn btn-dark btn-lg">
                                    Go to CRM Admin Panel
                                </a>
                            @else
                                <a href="{{ route('tenant.billing') }}" class="btn btn-primary btn-lg">
                                    Subscribe now
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($usage)
                <div class="card shadow-sm mt-4">
                    <div class="card-header">Current Usage</div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            @foreach ([
                                'Brands' => $usage->total_brands,
                                'Sellers' => $usage->total_sellers,
                                'Admins' => $usage->total_admins,
                                'Clients' => $usage->total_clients,
                                'Orders' => $usage->total_orders,
                                'Leads (month)' => $usage->leads_this_month,
                            ] as $label => $count)
                                <div class="col-6 col-md-2">
                                    <div class="border rounded p-3">
                                        <div class="fw-bold fs-4">{{ $count }}</div>
                                        <div class="small text-muted">{{ $label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
