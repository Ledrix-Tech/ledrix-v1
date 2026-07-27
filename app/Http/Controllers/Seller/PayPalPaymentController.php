<?php

// app/Http/Controllers/PayPalPaymentController.php
namespace App\Http\Controllers\Seller;

use App\Models\PaymentLink;
use Illuminate\Http\Request;
use App\Services\PayPalGateway;
use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayFactory;

class PayPalPaymentController extends Controller
{
    /** Start checkout (redirect to PayPal) */
    public function createCheckout(Request $request, string $token, PayPalGateway $paypal)
    {
        $link = PaymentLink::with(['order', 'lead'])->where('token', $token)->firstOrFail();
        abort_if(!$link->isActiveLink(), 410, 'This payment link is no longer active.');

        $buyer = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:255'],
            'state'      => ['nullable', 'string', 'max:255'],
            'zip'        => ['nullable', 'string', 'max:30'],
            'country'    => ['nullable', 'string', 'max:255'],
        ]);

        $session = $paypal->createCheckout($link, $buyer);

        return redirect()->away($session['url']);
    }

    public function paypalReturn(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        $link = PaymentLink::with('brand', 'order')->where('token', $token)->firstOrFail();
        $brand = $link->brand ?? $link->order->brand;
        abort_if(!$brand, 500, 'Missing brand');
        $paypalOrderId = $request->query('token');
        $gateway = $factory->forProviderWithBrand('paypal', $brand);
        $gateway->handleCheckoutSuccess($link, $paypalOrderId);
        return redirect()->route('paid-success', $token);
    }

    /** Optional thank-you page (you already have) */
    public function successPaid(string $token)
    {
        // $link = PaymentLink::with('order')->where('token', $token)->firstOrFail();
        // always fetch fresh; eager load order + latest successful payment
        $link = PaymentLink::with([
            'order' => fn($q) => $q->with(['payments' => fn($p) => $p->latest('id')]),
            'brand:id,brand_name',
        ])->where('token', $token)->firstOrFail();

        $order  = $link->order;
        $latest = $order?->payments?->first(); // may be null if webhook delay
        return view('paid-success', compact('link', 'order', 'latest'));
    }

    /** Webhook */
    public function handle(Request $request, PayPalGateway $paypal)
    {
        $ok = $paypal->handleWebhook($request->getContent(), $request->headers->all());
        return response()->json(['ok' => $ok]);
    }

    /** Optional thank-you page (you already have) */
    public function thanks(string $token)
    {
        // $link = PaymentLink::with('order')->where('token', $token)->firstOrFail();
        // always fetch fresh; eager load order + latest successful payment
        $link = PaymentLink::with([
            'order' => fn($q) => $q->with(['payments' => fn($p) => $p->latest('id')]),
            'brand:id,brand_name',
        ])->where('token', $token)->firstOrFail();

        $order  = $link->order;
        $latest = $order?->payments?->first(); // may be null if webhook delay
        return view('paid-success', compact('link', 'order', 'latest'));
    }

}
