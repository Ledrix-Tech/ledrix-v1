@extends('front.layout.layout')

@section('title', 'Payment Successful')

@section('robots', 'noindex, nofollow')

@section('main-content')

    <header class="hero d-flex align-items-center justify-content-center text-center"
        style="
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
              url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center;
  background-size: cover;
  min-height: 280px;">
        <div class="container text-white">
            <h1>{{ $company->name ?? 'N/A' }}</h1>
            <p>{{ $company->website ?? 'No website linked' }}</p>
        </div>
    </header>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <div class="mb-3">
                        <h2 class="text-success">🎉 Payment Successful</h2>
                    </div>
                    <hr>
                    <p>Thank you <strong>{{ $company->name ?? 'N/A' }}</strong>!</p>
                    <p>Your subscription for
                        <strong>{{ ucfirst(str_replace('-', ' ', $package->pkg_name)) }}</strong> is now <span
                            class="badge bg-success">Active</span>.
                    </p>

                    <ul class="list-group mb-3 text-start">
                        <li class="list-group-item">Plan:
                            {{ ucfirst(str_replace('-', ' ', $package->pkg_name)) }}
                        </li>
                        <li class="list-group-item">Subscription Start:
                            {{ $membership->start_date->format('d M, Y') }}</li>
                        <li class="list-group-item">Subscription End:
                            {{ $membership->end_date->format('d M, Y') }}</li>
                        <li class="list-group-item">API Key: <code>{{ $membership->api_key }}</code></li>
                        @if (!empty($plainPassword))
                            <li class="list-group-item bg-light">
                                <strong>Login Email:</strong> {{ $company->email }} <br>
                                <strong>Login Password:</strong>
                                <code>{{ $plainPassword }}</code>
                                <br>
                                <small class="text-danger">⚠️ Copy this password now — it won’t be shown
                                    again.</small>
                            </li>
                        @endif
                    </ul>
                    <div class="btnBox text-center">
                        <a href="{{ route('tenant.login') }}" class="btn btn-primary w-50">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
