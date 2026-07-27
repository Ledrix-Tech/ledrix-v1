<?php

namespace App\Http\Controllers\Seller;

use Stripe\Stripe;
use App\Models\Lead;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentLink;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PaymentLinkCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentLinkNotification;


class StripePaymentController extends Controller
{
    public function sellerGenerateLink(Request $request, Brand $brand, Lead $lead)
    {
        $seller = auth('seller')->user();

        // abort_unless($seller && $seller->id === $lead->seller_id && $seller->brand_id === $brand->id, 403); // seller generate link for ow leads
        abort_unless($seller && $seller->id === $lead->seller_id || $seller->brand_id === $brand->id, 403); // seller generate link for all leads

        $data = $request->validate([
            'service_name'     => ['required', 'string', 'max:255'],
            'currency'         => ['required', 'string', 'size:3'],
            'total_amount'     => ['required', 'numeric', 'gt:0'],      // e.g. 4000.00 (order TOTAL)
            'payable_amount'   => ['required', 'numeric', 'gt:0'],      // e.g. 2000.00 (this INSTALLMENT)
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'description'      => ['nullable', 'string', 'max:1000'],
        ]);

        // Optional local guard: can’t charge more than total in one go.
        if ((float)$data['payable_amount'] > (float)$data['total_amount']) {
            return back()->withErrors(['payable_amount' => 'Payable amount cannot exceed total amount.'])->withInput();
        }

        $totalCents  = (int) round($data['total_amount'] * 100);
        $payNowCents = (int) round($data['payable_amount'] * 100);
        $currency    = strtoupper($data['currency']);

        $link = DB::transaction(function () use ($brand, $lead, $seller, $data, $totalCents, $payNowCents, $currency) {
            // Ensure client exists + associated to lead
            $client = $lead->client_id
                ? $lead->client
                : Client::firstOrCreate(['email' => $lead->email], ['name' => $lead->name]);

            if (!$lead->client_id) {
                $lead->client()->associate($client);
                $lead->save();
            }

            // Reuse an open order for this client+brand+service (typical for installments)
            $order = Order::query()
                ->where('brand_id',   $brand->id)
                // ->where('seller_id',  $seller->id)      // Front seller owns the order here
                ->where('seller_id',  $lead->seller_id)      // project manager seller own order here
                ->where('client_id',  $client->id)
                ->where('service_name', $data['service_name'])
                // ->where('lead_id', $lead->id)        // only if you truly want one order per lead and have backfilled old rows
                ->whereIn('status', ['draft', 'pending'])
                ->lockForUpdate()
                ->first();

            if (!$order) {
                // First installment → create the order with TOTAL
                $order = Order::create([
                    'lead_id'      => $lead->id,
                    'brand_id'     => $brand->id,
                    // 'seller_id'    => $seller->id, // Front seller owns PM's lead here
                    'seller_id' =>  $lead->seller_id,
                    'client_id'    => $client->id,
                    'service_name' => $data['service_name'],
                    'currency'     => $currency,
                    'unit_amount'  => $totalCents,   // order TOTAL
                    'status'       => 'pending',
                    'buyer_name'   => $lead->name,
                    'buyer_email'  => $lead->email,
                ]);
            } else {
                // Currency must match existing order
                abort_unless($order->currency === $currency, 422, 'Currency mismatch with existing order.');

                // Allow increasing the order total if you quoted higher; block decreases
                if ($totalCents !== (int)$order->unit_amount) {
                    abort_unless($totalCents >= (int)$order->unit_amount, 422, 'Cannot reduce order total.');
                    $order->unit_amount = $totalCents;
                    $order->save(); // triggers model saving() to recompute balance_due/status
                }
            }

            // Guard: cannot over-collect
            abort_unless($payNowCents <= (int)$order->balance_due, 422, 'Payable exceeds remaining balance.');

            $hours = (int) ($data['expires_in_hours'] ?? 24 * 7);
            $hours = max(1, min(720, $hours));

            // Create a PaymentLink for THIS installment
            return PaymentLink::create([
                'lead_id'      => $lead->id,
                'seller_id'    => $seller->id,
                'brand_id'     => $brand->id,
                'client_id'    => $client->id,
                'order_id'     => $order->id,
                'service_name' => $data['service_name'],
                'description'  => $data['description'] ?? null,
                'currency'     => $currency,
                'unit_amount'  => $payNowCents,                 // PAY NOW
                'order_total_snapshot' => (int)$order->unit_amount, // for display
                'token'        => Str::random(48),
                'status'       => 'active',
                'expires_at'   => now()->addHours($hours),
                // 'provider' => $data->string('provider')->lower()->value() === 'paypal' ? 'paypal' : 'stripe',
            ]);
        });

        // Build signed, integrity-checked URL
        $payload = [
            't'   => $link->token,
            'a'   => $link->unit_amount,   // minor units
            'c'   => $link->currency,
            's'   => $link->service_name,
            'exp' => optional($link->expires_at)?->timestamp,
        ];
        $p = Crypt::encryptString(json_encode($payload));

        // (Either use the helper or this signed route — your choice)
        $url = $link->signedUrl(); // uses a default 7-day signature window
        // Or:
        // $url = URL::temporarySignedRoute('paylinks.show', $link->expires_at ?? now()->addDays(7), ['token'=>$link->token, 'p'=>$p]);

        // For uipdate provider Stripe or PayPal
        // $link->provider = $request->string('provider')->lower()->value() === 'paypal' ? 'paypal' : 'stripe';
        // $link->save();

        $link->update([
            'last_issued_url'        => $url,
            'last_issued_at'         => now(),
            'last_issued_expires_at' => $link->expires_at ?? now()->addDays(7),
        ]);

        // Notify the client (and optionally CC the seller)
        Notification::route('mail', $lead->email)
            ->notify(new PaymentLinkNotification($link, $url));
        // Mail::to($lead->email)->cc($seller->email)->send(new PaymentLinkCreated($link, $url));

        return back()
            ->with('success', 'Payment link created.')
            ->with('payment_link_url', $url);
    }

    public function createCheckout(Request $request, string $token)
    {
        $link = PaymentLink::with(['order', 'lead'])->where('token', $token)->firstOrFail();
        abort_if(! $link->isActiveLink(), 410, 'This payment link is no longer active.');
        // If you collect country *names*:
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:255'],
            'state'      => ['nullable', 'string', 'max:255'],
            'zip'        => ['nullable', 'string', 'max:30'],
            'country'    => ['nullable', 'string', 'max:255'],  // ← was size:2 (ISO code)
        ]);
        // Debug cleanly (remove in prod):

        $context = [
            'return_url' => route('paypal.return', $token), // used by PayPal only
            'cancel_url' => route('paylinks.show', $token) . '?canceled=1',
            // add whatever else your service needs
        ];
        // In createCheckout()
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::create([
            'mode'        => 'payment',
            'success_url' => route('paylinks.success', $token) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('paylinks.show', $token) . '?canceled=1',
            'line_items'  => [[
                'price_data' => [
                    'currency'     => strtolower($link->currency),
                    'product_data' => ['name' => $link->service_name],
                    'unit_amount'  => $link->unit_amount,
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $validated['email'],
            'metadata' => [
                'payment_link_id'   => (string) $link->id,
                'order_id'          => (string) $link->order_id,
                'lead_id'           => (string) $link->lead_id,
                'payment_link_token' => $link->token, // <— add this
            ],
            'billing_address_collection' => 'required',
        ], [
            'idempotency_key' => 'paylink_' . $link->token,
        ]);

        Log::info('Checkout session created', [
            'session_id' => $session->id,
            'meta'       => $session->metadata,
        ]);
        $link->update(['provider_session_id' => $session->id]);
        $link->order?->update(['provider_session_id' => $session->id]);
        // dd($request->all(), $token, $link,$session);

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        // Client lands here after payment — you can show a thank-you page.
        return view('payments.success');
    }

    public function cancel()
    {
        return view('payments.cancel');
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature invalid', ['err' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }
        Log::info('Stripe webhook received', ['type' => $event->type]);
        // Normalize inputs we need: order_id, link_id, amount, currency, piId, sessionId
        $session    = null;
        $intent     = null;
        $orderId    = null;
        $linkId     = null;
        $amount     = null;   // cents
        $currency   = null;
        $piId       = null;
        $sessionId  = null;

        // Expand if needed
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        if ($event->type === 'checkout.session.completed' || $event->type === 'checkout.session.async_payment_succeeded') {
            $session   = $event->data->object;
            $sessionId = $session->id ?? null;
            $piId      = $session->payment_intent ?? null;
            $amount    = isset($session->amount_total) ? (int)$session->amount_total : null;
            $currency  = $session->currency ?? null;
            $orderId   = isset($session->metadata->order_id) ? (int)$session->metadata->order_id : null;
            $linkId    = isset($session->metadata->payment_link_id) ? (int)$session->metadata->payment_link_id : null;

            // If we didn't get totals on the session, fetch the PI
            if (!$amount || !$currency || !$piId) {
                $intent = $piId ? \Stripe\PaymentIntent::retrieve($piId, ['expand' => ['charges']]) : null;
                if ($intent) {
                    $amount   = (int)($intent->amount_received ?? $intent->amount ?? 0);
                    $currency = $intent->currency ?? $currency;
                }
            }
        } elseif ($event->type === 'payment_intent.succeeded') {
            $intent   = $event->data->object;
            $piId     = $intent->id ?? null;
            $amount   = (int)($intent->amount_received ?? $intent->amount ?? 0);
            $currency = $intent->currency ?? null;

            // Try to recover metadata set in Checkout
            $orderId  = isset($intent->metadata->order_id) ? (int)$intent->metadata->order_id : null;
            $linkId   = isset($intent->metadata->payment_link_id) ? (int)$intent->metadata->payment_link_id : null;

            // If created via Checkout, we can also try to find the session by latest charge
            if (!$orderId || !$linkId) {
                try {
                    $cs = \Stripe\Checkout\Session::all(['payment_intent' => $piId, 'limit' => 1]);
                    if (!empty($cs->data)) {
                        $session   = $cs->data[0];
                        $sessionId = $session->id;
                        $orderId   = $orderId ?: (int)($session->metadata->order_id ?? 0);
                        $linkId    = $linkId ?: (int)($session->metadata->payment_link_id ?? 0);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } else {
            // Ignore other events; return 200 so Stripe stops retrying
            return response()->json(['ignored' => $event->type]);
        }
        Log::info('Stripe normalized', compact('orderId', 'linkId', 'amount', 'currency', 'piId', 'sessionId'));

        // handle()
        if (!in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'])) {
            return response()->json(['ignored' => $event->type]);
        }

        $session   = $event->data->object;
        $sessionId = $session->id ?? null;
        $piId      = $session->payment_intent ?? null;
        $amount    = (int) ($session->amount_total ?? 0);
        $currency  = $session->currency ?? null;
        $orderId   = (int) ($session->metadata->order_id ?? 0);
        $linkId    = (int) ($session->metadata->payment_link_id ?? 0);

        // hard requirements
        if (!$orderId || !$linkId || !$amount || !$currency || !$piId) {
            Log::warning('Stripe missing critical', compact('orderId', 'linkId', 'amount', 'currency', 'piId'));
            return response()->json(['skipped' => true]);
        }

        // Get extra receipt/card info (optional)
        $charge = null;
        try {
            if (!$intent && $piId) {
                $intent = \Stripe\PaymentIntent::retrieve($piId, ['expand' => ['charges']]);
            }
            if ($intent && $intent->charges && !empty($intent->charges->data)) {
                $charge = $intent->charges->data[0];
            }
        } catch (\Throwable $e) {
        }

        $receiptUrl = $charge->receipt_url ?? null;
        $cardBrand  = $charge->payment_method_details->card->brand ?? null;
        $last4      = $charge->payment_method_details->card->last4 ?? null;

        // after normalizing $orderId, $linkId, $amount, $currency, $piId, $sessionId:
        if (!$orderId || !$linkId || !$amount || !$currency || !$piId) {
            Log::warning('Stripe missing critical', compact('orderId', 'linkId', 'amount', 'currency', 'piId'));
            return response()->json(['skipped' => true]);
        }

        DB::transaction(function () use ($orderId, $linkId, $amount, $currency, $piId, $sessionId, $receiptUrl, $cardBrand, $last4, $session) {
            $link  = \App\Models\PaymentLink::lockForUpdate()->findOrFail($linkId);
            $order = \App\Models\Order::lockForUpdate()->findOrFail($orderId);


            // HARD STOP if metadata order ≠ link’s order
            if ((int)$link->order_id !== (int)$order->id) {
                Log::error('Order mismatch — refusing to credit', [
                    'meta_order' => $orderId,
                    'link_order' => $link->order_id,
                    'link_id'    => $link->id,
                ]);
                throw new \RuntimeException('Order mismatch');
            }

            // idempotency
            if (\App\Models\Payment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $piId)->exists()
            ) {
                Log::info('Payment already recorded, skipping', ['pi' => $piId]);
                return;
            }

            $remaining = max(0, (int)$order->unit_amount - (int)$order->amount_paid);
            $credit    = min((int)$amount, $remaining);


            $payment = Payment::create([
                'order_id'                  => $order->id,
                'payment_link_id'           => $link->id,
                'amount'                    => $credit,
                'currency'                  => strtoupper($currency),
                'status'                    => 'succeeded',
                'provider'                  => 'stripe',
                'provider_payment_intent_id' => $piId,
                'payload'                   => $session ? $session->toArray() : ['pi' => $piId],
            ]);

            if ($link->status !== 'paid') {
                $link->update([
                    'status'                   => 'paid',
                    'paid_at'                  => now(),
                    'provider_payment_intent_id' => $piId,
                    'provider_session_id'        => $sessionId,
                    'payment_card_brand'       => $cardBrand,
                    'provider_receipt_url'       => $receiptUrl,
                ]);
            }

            $order->amount_paid = (int)$order->amount_paid + $credit;
            $order->provider_payment_intent_id = $piId;
            $order->provider_session_id        = $sessionId;

            // dd($link, $order, $remaining, $credit);

            $order->save(); // booted() recalculates balance_due/status
        });
        return response()->json(['ok' => true]);
    }

    public function checkoutSuccess(Request $request, string $token)
    {
        $link = PaymentLink::with('order')->where('token', $token)->firstOrFail();

        // dd($request->all(), $link,$link->order);

        if ($request->filled('session_id')) {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve($request->query('session_id'));

            if (($session->payment_status ?? null) === 'paid') {
                $piId = $session->payment_intent ?? null;

                // insert into payments if webhook hasn’t done it
                if ($link->order && $piId && !Payment::where('provider', 'stripe')->where('provider_payment_intent_id', $piId)->exists()) {
                    Payment::create([
                        'order_id'  => $link->order->id,
                        'payment_link_id' => $link->id,
                        'amount'    => (int)$link->unit_amount,      // this link’s payable-now
                        'currency'  => $link->currency,
                        'status'    => 'succeeded',
                        'provider'  => 'stripe',
                        'provider_payment_intent_id' => $piId,
                        'payload'   => $session->toArray(),
                    ]);
                }

                // ⚠️ Don’t force order->status = 'paid' here; let amounts decide.
                // Update running totals instead:
                if ($link->order) {
                    // roll up using the model (NOT builder)
                    $order = Order::lockForUpdate()->findOrFail($link->order_id);
                    $order->amount_paid = (int)$order->amount_paid + (int)$link->unit_amount;
                    $order->provider_payment_intent_id = $piId;
                    $order->provider_session_id        = $session->id;
                    $order->save();

                    // Order::whereKey($link->order_id)->lockForUpdate()->update([
                    //     'amount_paid'  => DB::raw('amount_paid + ' . (int)$link->unit_amount),
                    //     'balance_due'  => DB::raw('GREATEST(unit_amount - (amount_paid + ' . (int)$link->unit_amount . '), 0)'),
                    //     // status: paid if new balance is 0 else pending
                    //     'status'       => DB::raw('CASE WHEN (unit_amount - (amount_paid + ' . (int)$link->unit_amount . ')) <= 0 THEN "paid" ELSE "pending" END'),
                    //     'paid_at'      => DB::raw('CASE WHEN (unit_amount - (amount_paid + ' . (int)$link->unit_amount . ')) <= 0 AND unit_amount > 0 THEN NOW() ELSE paid_at END'),
                    //     'provider_payment_intent_id' => $piId,
                    //     'provider_session_id'        => $session->id,
                    // ]);
                }

                if ($link->status !== 'paid') {
                    $link->update([
                        'status'            => 'paid',
                        'paid_at'           => now(),
                        'provider_session_id' => $session->id,
                        'provider_payment_intent_id' => $piId,
                    ]);
                }
            }
        }

        return view('paid-success', ['link' => $link, 'order' => $link->order]);
    }
}
