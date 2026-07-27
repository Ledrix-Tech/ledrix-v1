<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Client Relationship Management</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(config('branding.fav-icon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            background: #f0f2f8;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Background decorative shapes */
        .bg-shape {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            opacity: 0.3;
            z-index: -1;
        }

        .bg-shape.one {
            background: #a18aff;
            top: 10%;
            left: 5%;
        }

        .bg-shape.two {
            background: #f7b63e;
            bottom: 10%;
            right: 8%;
        }

        .bg-shape.three {
            background: #db165b94;
            top: 22%;
            right: 16%;
        }

        .card-container {
            max-width: 900px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: #ffffff;
            padding: 1rem 2rem;
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .nav-link {
            color: #555 !important;
            margin-right: 1rem;
        }

        .btn-get-started {
            background-color: #673187;
            color: white;
            border: none;
            padding: 0.4rem 1.2rem;
            border-radius: 20px;
        }

        .btn-get-started:hover {
            background-color: #4f50b0;
        }

        .card-body {
            padding: 2rem 2rem;
        }

        .hero-row {
            align-items: center;
        }

        .hero-text h1 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #222;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.05rem;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .btn-learn {
            border: 2px solid #673187;
            background: transparent;
            color: #673187;
            padding: 0.5rem 1.4rem;
            border-radius: 20px;
            transition: 0.3s;
        }

        .btn-learn:hover {
            background: linear-gradient(135deg, #4338C9, #8C52FF, #05BD64);
            color: white;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2.2rem;
            }

            .card-body {
                padding: 1.5rem;
            }
        }

        .bg-gradient-3 {
            background: linear-gradient(135deg, #4338C9, #8C52FF, #05BD64);
            color: white !important;
        }
    </style>
</head>

<body>
    <!-- Decorative Background Circles -->
    <div class="bg-shape one"></div>
    <div class="bg-shape two"></div>
    <div class="bg-shape three"></div>

    <div class="card-container">
        <!-- Header / Navbar inside the card -->
        <div class="card-header">
            <nav class="navbar navbar-expand-lg p-0">
                <a class="navbar-brand brand-logo" href="{{ route('admin.login.get') }}">
                    <img src="{{ url('admin-assets/dpm-logos/logo-ic.png') }}" alt="" style="width: 200px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navMenu">
                    {{-- <ul class="navbar-nav me-3 mb-0">
                        <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    </ul> --}}
                    <a href="{{ route('admin.login.get') }}" class="btn btn-get-started bg-gradient-3">Admin</a>
                    &nbsp;
                    <a href="{{ route('seller.login.get') }}" class="btn btn-get-started bg-gradient-3">Seller</a>
                    &nbsp;
                    {{-- <a href="{{ route('upwork.login.get') }}" class="btn btn-get-started bg-gradient-3">Upwork</a> --}}
                </div>
            </nav>
        </div>

        <div class="card-body">
            <div class="row hero-row">
                <!-- Text -->
                <div class="col-lg-6 hero-text">
                    <h1>Customer <br> Relationship Management</h1>
                    <p>
                        Streamline your project workflows, team collaboration, and client communication —
                        all in one smart CRM platform tailored for project managers.
                    </p>
                    <a href="{{ route('client.login.get') }}" class="btn btn-learn">Client Portal</a>
                </div>
                <!-- Image -->
                <div class="col-lg-6 text-lg-end text-center mt-4 mt-lg-0">
                    <img src="{{ url('user-assets/front-banner.jpg' ?? 'https://st.depositphotos.com/1557418/1846/v/450/depositphotos_18463379-stock-illustration-computer-networks.jpg') }}"
                        alt="CRM Illustration" class="hero-image" />
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
