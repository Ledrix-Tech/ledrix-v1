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

    @php
        $isOrder = isset($order) && $order;
        $currency = old('currency', $isOrder ? $order->currency : 'USD');

        $serviceName = old('service_name', $isOrder ? $order->service_name ?? '' : '');
        $totalDefault = $isOrder ? number_format(($order->unit_amount ?? 0) / 100, 2, '.', '') : old('total_amount');

        $dueCents = (int) ($isOrder ? $order->balance_due ?? 0 : 0);
        $payNowDefault = old('payable_amount', $isOrder ? number_format($dueCents / 100, 2, '.', '') : '');
    @endphp

    <div class="payment-container">
        <div class="text-center mb-4">
            <h3>GENERATE PAYMENT LINK</h3>
        </div>
        <hr>
        <form method="POST"
            action="{{ route('upwork.link-generator.final', [
                'order' => $order->id ?? null, // include when present
            ]) }}">
            @csrf

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Client Name</label>
                    <input type="text" class="form-control" value="{{ $order->client->name ?? '—' }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Client Email</label>
                    <input type="text" class="form-control" value="{{ $order->client->email ?? '—' }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Brand</label>
                    <input type="text" class="form-control" value="{{ $order->brand->brand_name ?? '—' }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Currency</label>
                    <input type="text" class="form-control" name="currency" value="{{ $currency }}" readonly>
                </div>

                <div class="form-group col-md-12">
                    <label>Service Name</label>
                    <input type="text" class="form-control" name="service_name" value="{{ $serviceName }}" readonly>
                </div>

                <div class="form-group col-md-6">
                    <label>Payment Provider</label>
                    <select name="provider" class="form-control" required>
                        <option value="stripe" @selected(old('provider') === 'stripe')>Stripe</option>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label>Expires in (hours)</label>
                    <input type="number" class="form-control" name="expires_in_hours" min="1" max="720"
                        value="{{ old('expires_in_hours', 24) }}">
                </div>

                <div class="form-group col-md-6">
                    <label>Total Amount</label>
                    <input type="number" class="form-control" value="{{ $totalDefault }}" readonly>
                    <small class="text-muted">Taken from the order.</small>
                </div>

                <div class="form-group col-md-6">
                    <label>Pay Now Amount (≤ due)</label>
                    <input type="number" class="form-control" name="payable_amount" step="0.01"
                        placeholder="e.g., 2000.00" value="{{ $payNowDefault }}" required>
                    <small class="text-muted">
                        Due: {{ number_format($dueCents / 100, 2) }} {{ $order->currency }}
                    </small>
                </div>
            </div>

            <div class="text-center">
                @if ($dueCents === 0)
                    <button type="button" class="btn-submit" disabled>Paid in full</button>
                @else
                    <button type="submit" class="btn-submit bg-gradient-3">Generate Link</button>
                @endif
            </div>
        </form>
    </div>

    @if ($isOrder)
        <script>
            document.addEventListener('input', function(e) {
                if (e.target.name === 'payable_amount') {
                    const due = {{ $dueCents }} / 100;
                    const val = parseFloat(e.target.value || '0');
                    if (val > due) e.target.value = due.toFixed(2);
                    if (val < 0) e.target.value = '0.00';
                }
            });
        </script>
    @endif


@endsection
