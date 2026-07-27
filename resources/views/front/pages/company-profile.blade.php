@extends('front.layout.layout')

@section('title', 'Zentra | Dashboard')

@section('main-content')

    <header class="hero d-flex align-items-center justify-content-center text-center"
        style="
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
              url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center;
  background-size: cover;
  min-height: 280px;">
        <div class="container text-white">
            <h1>{{ $company->name }}</h1>
            <p>{{ $company->website ?? 'No website linked' }}</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <div class="row g-4">

                <!-- Company Info -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">Company Details</div>
                        <div class="card-body" style="height: 300px;">
                            <p><strong>Name:</strong> {{ $company->name }}</p>
                            <p><strong>Email:</strong> {{ $company->email }}</p>
                            <p><strong>Domain:</strong> {{ $company->website ?? 'N/A' }}</p>
                            <p><strong>Joined:</strong> {{ $company->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Subscription Info -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">Subscription</div>
                        <div class="card-body" style="height: 300px;">
                            @if ($subscription)
                                <p><strong>Plan:</strong> {{ ucfirst($subscription->package_type) }}</p>
                                <p><strong>Start:</strong>
                                    {{ \Carbon\Carbon::parse($subscription->start_date)->format('M d, Y') }}</p>
                                <p><strong>End:</strong>
                                    {{ \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') }}</p>
                                <p><strong>Status:</strong>
                                    <span class="badge bg-{{ $subscription->status == 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </p>
                                <hr>

                                @if ($subscription->isActive())
                                    <p><strong>Remaining Time:</strong>
                                        <span class="badge bg-success">
                                            {{ $subscription->remainingDays() }} days
                                        </span>
                                    </p>
                                @else
                                    <p><strong>Expired:</strong>
                                        <span class="badge bg-danger">
                                            {{ abs($subscription->remainingDays()) }} days ago
                                        </span>
                                    </p>
                                @endif
                            @else
                                <p class="text-muted">No active subscription</p>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- API Key + Limits -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">API & Limits</div>
                        <div class="card-body" style="height: 300px;">
                            <p><strong>API Key:</strong><br>
                                <code>{{ $subscription->api_key ?? 'Not generated yet' }}</code>
                            </p>
                            @if ($limits)
                                <ul class="list-unstyled small">
                                    <li>Admins: {{ $limits->max_admins }}</li>
                                    <li>Users: {{ $limits->max_users }}</li>
                                    <li>Clients: {{ $limits->max_clients }}</li>
                                    <li>Leads: {{ $limits->max_leads }}</li>
                                </ul>
                            @else
                                <p class="text-muted">No limits set</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Go to CRM -->
            {{-- <div class="text-center mt-5">
                <a href="{{ route('admin.login.get') }}" class="btn btn-dark btn-lg px-5">
                    🚀 Go to CRM Panel
                </a>
            </div> --}}
            @if (auth('companies')->check())
                <div class="text-center mt-5">
                    <a href="{{ route('company-goto-crm') }}" class="btn btn-dark btn-lg px-5">
                        🚀 Go to CRM Panel
                    </a>
                </div>
            @endif
        </div>
    </main>


@endsection
