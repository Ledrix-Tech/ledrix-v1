<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Link</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link href="{{ url('admin-assets/dpm-logos/dpm-fav.png') }}" rel="icon">

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous">
    </script>
</head>

<body>

    <style>
        body {
            background: #f9f9f9;
        }

        .payment-container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            max-width: 900px;
            margin: 30px auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            background: #00A9B7;
            color: #fff;
            padding: 5px 10px;
            margin-top: 20px;
            font-weight: bold;
        }

        .btn-submit {
            background: #4CAF50;
            color: white;
            font-weight: bold;
            border: none;
            padding: 10px 25px;
            border-radius: 20px;
        }

        .safe-checkout {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .safe-checkout img {
            height: 50px;
        }

        .brand-logo {
            max-height: 60px;
        }
    </style>

    @php
        $iconUrl =
            filter_var($brand->brand_url, FILTER_VALIDATE_URL) &&
            !preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $brand->brand_url)
                ? 'https://www.google.com/s2/favicons?sz=64&domain=' . parse_url($brand->brand_url, PHP_URL_HOST)
                : $brand->brand_url;

        // $payKeys = DB::table('account_keys')->where('brand_id', $brand->id)->first();
        // // dd($payKeys);

        // Upwork module: no brand / no account_keys

        $fullName = $client->name ?? 'Client';
        $nameParts = preg_split('/\s+/', trim($fullName), 2);

        $fName = $nameParts[0] ?? '';
        $lName = $nameParts[1] ?? '';

        // Optional: show order id or type if you want
        $orderLabel = $order ? 'Order #' . $order->id . ' • ' . strtoupper($order->order_type ?? 'original') : null;

        // Optional: your own static logo (recommended)
        $logoUrl = asset('images/logo.png'); // change to your logo path
    @endphp

    <div class="payment-container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h3>Billing Details</h3>
            <img src="{{ $iconUrl ?? $logoUrl }}" alt="Logo" class="brand-logo" id="brandLogo">
        </div>

        @if ($orderLabel)
            <div class="mb-3 text-muted small">{{ $orderLabel }}</div>
        @endif

        <form method="POST" action="{{ route('upwork.paylinks.checkout', $token) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label>Service Package</label>
                    <input type="text" class="form-control" value="{{ $service ?? '' }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Amount Payable</label>
                    <input type="text" class="form-control"
                        value="{{ isset($amount) ? number_format($amount / 100, 2) : '' }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Currency</label>
                    <input type="text" class="form-control" value="{{ $currency ?? 'USD' }}" readonly>
                </div>
            </div>

            {{-- Payer --}}
            <div class="section-header mb-3">PAYER DETAILS</div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="text" name="first_name" value="{{ $fName }}" class="form-control"
                        placeholder="First name" readonly>
                </div>
                <div class="form-group col-md-6">
                    <input type="text" name="last_name" value="{{ $lName }}" class="form-control"
                        placeholder="Last name" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="email" name="email" value="{{ $client->email ?? '' }}" class="form-control"
                        placeholder="Email" readonly required>
                </div>
                <div class="form-group col-md-6">
                    <input type="text" name="phone" value="{{ $client->phone ?? '' }}" class="form-control"
                        placeholder="Phone" readonly>
                </div>
            </div>

            <hr>

            {{-- Provider --}}
            <div class="text-center">
                <button type="submit" class="btn btn-submit">
                    @if (($link->provider ?? '') === 'paypal')
                        <i class="fab fa-paypal"></i> Pay with PayPal
                    @else
                        <i class="fab fa-cc-stripe"></i> Pay with Stripe
                    @endif
                </button>
            </div>
        </form>
    </div>

</body>

</html>
