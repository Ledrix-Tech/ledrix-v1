<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset(config('branding.fav-icon')) }}">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet">
    <style>
        :root {
            /* Logo and favicon URLs */
            --brand-favicon: url('{{ config('branding.fav-icon') }}');
            --brand-logo: url('{{ config('branding.logo') }}');

            /* Gradient */
            --bg-gradient: {{ config('branding.bg-gradient') }};

            /* Colors */
            --color-primary: {{ config('branding.colors.primary') }};
            --color-secondary: {{ config('branding.colors.secondary') }};
            --color-success: {{ config('branding.colors.success') }};
            --color-danger: {{ config('branding.colors.danger') }};
            --color-warning: {{ config('branding.colors.warning') }};
            --color-info: {{ config('branding.colors.info') }};
            --color-white: {{ config('branding.colors.white') }};
        }

        .blurred {
            filter: blur(6px);
            position: relative;
            cursor: pointer;
        }

        /* Mobile-friendly table as cards */
        @media (max-width: 767px) {

            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive th,
            .table-responsive td,
            .table-responsive tr {
                display: block;
                width: 100%;
            }

            .table-responsive thead {
                display: none;
                /* hide table header */
            }

            .table-responsive tr {
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background: #fff;
                box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
            }

            .table-responsive td {
                text-align: left;
                padding: 8px;
                border: none;
                position: relative;
            }

            .table-responsive td::before {
                content: attr(data-label);
                /* show label */
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
        }

        @media (max-width: 480px) {
            .navbar .navbar-brand-wrapper {
                width: 150px;
            }
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .page-item {
            margin: 0 2px;
        }

        .page-link {
            background-color: #003d70;
            border-color: #003d70;
            color: #ffffff;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
        }

        .page-item.active .page-link {
            background-color: #ff0000;
            border-color: #ff0000;
        }

        .page-link.border_non_active:hover {
            background-color: #721111;
        }

        .sidebar .nav .nav-item.active>.nav-link:before {
            content: "";
            position: absolute;
            left: 0;
            top: .5rem;
            bottom: 0;
            background: #db165b;
            height: 24px;
            width: 4px;
        }

        .sidebar .nav .nav-item.active>.nav-link .menu-title {
            color: #f7b63e;
            font-family: "nunito-medium", sans-serif;
        }

        .sidebar .nav .nav-item .nav-link .icon-bg .menu-icon {
            color: #f7b63e;
        }

        .navbar .navbar-menu-wrapper .navbar-nav .nav-item.dropdown .dropdown-menu.navbar-dropdown .dropdown-item:hover {
            margin-bottom: 0;
            padding: 11px 13px;
            cursor: pointer;
            color: #111111;
            font-size: .875rem;
            font-style: normal;
            font-family: "nunito-medium", sans-serif;
        }

        .navbar-menu-wrapper,
        .sidebar,
        .proInfo,
        .bg-gradient-3 {
            background: var(--bg-gradient);
            color: white !important;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: #16181b;
            text-decoration: none;
            background-color: #00000040;
        }

        .sidebar .nav .nav-item.active {
            background: #000000;
        }

        .navbar .navbar-menu-wrapper .navbar-nav .nav-item.dropdown .dropdown-toggle:after {
            color: #db165b;
            font-size: 1.25rem;
            content: "\f35d";
        }

        .floating-badge {
            position: fixed;
            bottom: 15px;
            right: 20px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            /* light text */
            border-radius: 50px;
            background: rgba(59, 190, 245, 0.15);
            /* faint sky blue glass */
            box-shadow: inset 0 0 15px rgba(59, 190, 245, 0.25);
            pointer-events: none;
            z-index: 10000;
            overflow: hidden;

            /* Glassy effect */
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        /* Water wave overlay */
        .floating-badge::before {
            content: "";
            position: absolute;
            top: 0;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(59, 190, 245, 0.3), transparent 70%);
            opacity: 0.5;
            animation: wave 6s linear infinite;
        }

        /* Gentle wave motion */
        @keyframes wave {
            0% {
                transform: translateX(0) translateY(0) rotate(0deg);
            }

            50% {
                transform: translateX(-25%) translateY(-10%) rotate(180deg);
            }

            100% {
                transform: translateX(0) translateY(0) rotate(360deg);
            }
        }

        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --card-bg: #f9f9f9;
            --primary-color: #0066ff;
        }

        .dark-theme {
            --bg-color: #1e1e2f;
            --text-color: #ffffff;
            --card-bg: #2b2b3c;
            --primary-color: #3399ff;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .card {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>

    <div class="floating-badge">
        Zaryth Alpharos
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>

    <script>
        $(document).ready(function() {
            toastr.options.timeOut = 10000;

            @if (Session::has('success'))
                toastr.success('{{ Session::get('success') }}');
            @endif

            @if (Session::has('info'))
                toastr.info('{{ Session::get('info') }}');
            @endif

            @if (Session::has('warning'))
                toastr.warning('{{ Session::get('warning') }}');
            @endif

            @if (Session::has('error'))
                toastr.error('{{ Session::get('error') }}');
            @endif

            @if (Session::has('status'))
                toastr.info('{{ Session::get('status') }}');
            @endif
        });
    </script>

    <div class="container-scroller">

        @include('upwork.includes.top-bar')

        <div class="container-fluid page-body-wrapper">

            @include('upwork.includes.side-bar')

            <div class="main-panel">

                <div class="content-wrapper">

                    @yield('upwork-content')

                </div>

            </div>

        </div>

    </div>
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).on('click', function(event) {
            const modal = $('#ticketInfo');
            // if modal is open and click target is outside modal-content
            if (modal.hasClass('show') && $(event.target).closest('.modal-content').length === 0) {
                modal.modal('hide');
            }
        });

    </script>


</body>

</html>
