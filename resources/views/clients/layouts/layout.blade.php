<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ledrix Client Portal')</title>
    <link rel="icon" href="{{ asset(config('branding.fav-icon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('seller-assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('client-assets/css/client.css') }}">
    @stack('styles')
</head>

<body class="crm-body">
    <div class="crm-shell">
        @include('clients.includes.top-bar')
        <div id="crmSidebarOverlay" class="crm-sidebar-overlay"></div>
        @include('clients.includes.side-bar')

        <main class="crm-main">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        @yield('client-content')
                    </div>
                </div>
            </div>
        </main>
    </div>

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
    <script src="{{ asset('seller-assets/js/app.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
