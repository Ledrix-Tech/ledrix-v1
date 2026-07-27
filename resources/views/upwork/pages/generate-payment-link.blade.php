@extends('upwork.layout.layout')

@section('title', 'Upwork | Link Generator')

@section('upwork-content')

    <style>
        body {
            background: #f9f9f9;
        }

        .form-group ::placeholder {
            color: grey;
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

    @if (session('payment_link_url'))
        <div class="alert alert-info text-truncate" style="max-width: 100%;">
            <p>Payment link:</p>
            <div>
                <a href="{{ session('payment_link_url') }}" target="_blank" rel="noopener" class="d-inline-block text-truncate"
                    style="max-width: 90%;">
                    {{ session('payment_link_url') }}
                </a>
            </div>
            <button type="button" onclick="navigator.clipboard.writeText(`{{ session('payment_link_url') }}`)">
                Copy
            </button>
        </div>
    @endif

    <div class="payment-container">
        <div class="text-center mb-4">
            <h3>GENERATE PAYMENT LINK</h3>
        </div>

        <hr>

        <form method="POST" action="{{ route('upwork.generate-payment-link') }}" enctype="multipart/form-data">
            @csrf

            <!-- Client Details Section -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Client Name</label>
                    <input type="text" class="form-control" name="client_name" placeholder="Enter client name" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Client Email</label>
                    <input type="email" class="form-control" name="client_email" placeholder="Enter client email"
                        required>
                </div>
                <div class="form-group col-md-12">
                    <label>Client Phone</label>
                    <input type="text" class="form-control" name="client_phone" placeholder="Enter client phone"
                        required>
                </div>
            </div>
            <hr>

            <!-- Order Details Section -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Service Name</label>
                    <input type="text" class="form-control" id="service" name="service"
                        placeholder="e.g. Web Development">
                </div>
                <!-- Seller Selection Section -->
                <div class="form-group col-md-6">
                    <label>Choose Sell</label>
                    <select name="sell_type" class="form-control" required>
                        <option value="" disabled selected>-- select type --</option>
                        <option value="front">Front</option>
                        <option value="upsell">Up Sell</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Domain</label>
                    <select name="brandId" class="form-control" required>
                        <option value="" selected>-- select domain --</option>
                        @foreach ($domains as $item)
                            <option value="{{ $item->id }}">{{ $item->brand_name }} - <span
                                    class="text-sm">{{ $item->brand_url }}</span></option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Currency</label>
                    <input type="text" class="form-control" name="currency" value="USD"
                        placeholder="Enter currency (e.g., USD)" required readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>Total Amount (Price)</label>
                    <input type="number" class="form-control" name="unit_amount" placeholder="Enter price (e.g., 4000.00)"
                        required>
                </div>
                <div class="form-group col-md-6">
                    <label>Payable Now</label>
                    <input type="number" class="form-control" name="payable_amount"
                        placeholder="Enter amount to pay now (e.g., 2000.00)" required>
                </div>
            </div>

            <script>
                document.getElementById("service").addEventListener("input", function() {

                    let value = this.value;

                    // add space before capital letters
                    value = value.replace(/([a-z])([A-Z])/g, '$1 $2');

                    // capitalize first letter of each word
                    value = value.replace(/\b\w/g, function(letter) {
                        return letter.toUpperCase();
                    });

                    this.value = value;

                });
            </script>

            <hr>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Provider</label>
                    <input type="text" class="form-control" name="provider" value="stripe" readonly required>
                </div>

                <!-- Expiry and Final Section -->
                <div class="form-group col-md-6">
                    <label>Expires in (hours)</label>
                    <input type="number" class="form-control" name="expires_in_hours" min="1" max="3"
                        value="3" placeholder="e.g., 1">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn-submit bg-gradient-3">Generate Link</button>
            </div>
        </form>

    </div>


@endsection
