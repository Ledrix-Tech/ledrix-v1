<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin | Ledrix')</title>
    <meta name="theme-color" content="#312e81">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ledrix SA">
    <link rel="manifest" href="{{ asset('super-admin/manifest.webmanifest') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('super-admin/icon-192.png') }}?v=3">
    <link rel="icon" href="{{ asset(config('branding.fav-icon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('super-admin-assets/css/auth.css') }}">
</head>

<body>
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

    @yield('auth-content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('super-admin-assets/js/app.js') }}"></script>
    <script src="{{ asset('super-admin/pwa-register.js') }}" defer></script>
</body>

</html>
