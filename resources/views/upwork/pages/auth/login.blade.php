<!doctype html>
<html lang="en">

<head>
    <title>Upwork | Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('admin-assets/dpm-logos/dpm-fav.png') }}">

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ url('admin-assets/admin-auth-assets') }}/cstm.css">

    <style>
        body {
            width: 100%;
            height: 100vh;
            font-family: "Lato", Arial, sans-serif;
            align-content: center;
        }

        .ftco-section {
            padding: 20px 0;
        }

        .bg-gradient-3 {
            background: linear-gradient(135deg, #db165b, #673187, #f7b63e);
            color: white !important;
        }
    </style>
</head>

<body>
    <style>
        .floating-badge {
            position: fixed;
            bottom: 15px;
            right: 20px;
            background: #f1f1f1;
            /* light grey */
            color: #b0b3b7;
            /* muted text */
            font-size: 13px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 50px;
            /* fully rounded pill */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            /* subtle shadow */
            opacity: 0.9;
            pointer-events: none;
            /* so it won’t block clicks */
        }
    </style>

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
        });
    </script>
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-4 mx-auto text-center mb-3">
                    <div class="imgBox" style="width: 100%;">
                        <img src="{{ url('admin-assets/dpm-logos/11.png') }}" alt="" style="width: 100%">
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-wrap p-4 p-md-5">
                        <form action="{{ route('upwork.login.post') }}" method="post" class="login-form">
                            @csrf
                            <div class="form-group">
                                <input type="email" class="form-control rounded-left" name="email"
                                    placeholder="Email ..." required>
                            </div>
                            <div class="form-group d-flex">
                                <input type="password" class="form-control rounded-left" name="password"
                                    placeholder="Password" required>
                            </div>
                            <div class="form-group d-md-flex">
                                <div class="w-50">
                                </div>
                                <div class="w-50 text-md-right">
                                    <a href="{{ route('upwork.forgot.get') }}">Forgot Password!</a>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn rounded bg-gradient-3 submit p-3 px-5">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="{{ url('admin-assets/admin-auth-assets') }}/jquery.min.js"></script>
    <script src="{{ url('admin-assets/admin-auth-assets') }}/popper.js"></script>
    <script src="{{ url('admin-assets/admin-auth-assets') }}/bootstrap.min.js"></script>
    <script src="{{ url('admin-assets/admin-auth-assets') }}/main.js"></script>


</body>

</html>
