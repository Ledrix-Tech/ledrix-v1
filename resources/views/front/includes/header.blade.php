<!-- Header -->
{{-- <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm"> --}}
<nav class="navbar navbar-expand-lg shadow-sm smart-navbar" aria-label="Primary navigation">

    <div class="container">
        <a class="navbar-brand" href="{{ route('index.get') }}">
            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}"
                alt="Ledrix CRM — multi-tenant sales CRM software for agencies">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('features.get') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('pricing.get') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('about.get') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('faq.get') }}">FAQ</a></li>
                <li class="nav-item">
                    <a href="{{ route('contact-us.get') }}" class="nav-link">Contact</a>
                </li>
            </ul>
            @if (auth()->guard('tenant')->check())
            <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-primary btn-sm ms-3">
                <i class="fa fa-user"></i> Profile
            </a>
            @else
            <a href="{{ route('tenant.login') }}" class="btn btn-outline-primary btn-sm ms-3">
                <i class="fa fa-sign-in"></i> Sign in
            </a>
            @endif
        </div>
    </div>
</nav>