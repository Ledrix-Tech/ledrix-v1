<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @include('front.includes.seo')

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('seo.front_favicon_32', 'front-assets/imgs/favicon-32.png')) }}">
    <link rel="icon" type="image/png" href="{{ asset(config('seo.front_favicon', 'front-assets/imgs/fv-icon.png')) }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('front-assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
    <link rel="stylesheet" href="{{ asset('front-assets/css/lp.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet">
    @include('front.includes.analytics')
    @stack('head')
</head>

<body class="lp-body">
    <header class="lp-topbar">
        <div class="container lp-topbar-inner">
            <a class="lp-brand" href="{{ route('index.get') }}">
                <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM">
                <!-- <span>Ledrix</span> -->
            </a>
            <a href="{{ route('tenant.login') }}" class="lp-signin">Sign in</a>
        </div>
    </header>

    <main id="main-content" role="main">
        @yield('main-content')
    </main>

    <footer class="lp-footer">
        <div class="container">
            <p>© {{ date('Y') }} Ledrix · Multi-tenant sales CRM for agencies</p>
            <p class="lp-footer-links">
                <a href="{{ route('pricing.get') }}">Pricing</a>
                <a href="{{ route('contact-us.get') }}">Contact</a>
                <a href="{{ route('terms.get') }}">Terms</a>
                <a href="{{ route('privacy.get') }}">Privacy</a>
            </p>
        </div>
    </footer>

    <script>
        window.LedrixFlash = {!! json_encode([
            'success' => session('success'),
            'info' => session('info'),
            'warning' => session('warning'),
            'error' => session('error'),
            'status' => session('status'),
        ]) !!};
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('front-assets/js/app.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
