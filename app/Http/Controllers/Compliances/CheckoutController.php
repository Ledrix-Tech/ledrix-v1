<?php

namespace App\Http\Controllers\Compliances;

use App\Models\Lead;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PaymentLinkService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentLinkNotification;

class CheckoutController extends Controller
{
    public function generateLinkForm(Request $request, Brand $brand, Lead $lead, ?Order $order = null)
    {
        $seller  = auth('seller')->user();
        $admin   = auth('admin')->user();

        $orderType = $request->get('type'); // "renewal" or null
        $renewedOrder = Order::where('order_type', $orderType)->where('parent_order_id', $order)->first();
        // ✅ Admin always allowed
        if ($admin) {
            $canGenerate = true;
        } else {
            // ✅ Only front sellers of this brand
            $canGenerate = $seller
                && (($seller->role ?? $seller->is_seller) === 'front_seller')
                && ((int)$seller->brand_id === (int)$brand->id);
        }
        // Lead must belong to brand
        abort_unless((int)$lead->brand_id === (int)$brand->id, 404, 'Lead/brand mismatch.');
        // Check if allowed
        abort_unless($canGenerate, 403, 'Only admins or front sellers can generate a link.');

        // If order is given, validate
        if ($order) {
            if (
                (int)$order->brand_id !== (int)$brand->id ||
                (int)$order->client_id !== (int)$lead->client_id
            ) {
                return redirect()
                    ->route('admin.orders.get')
                    ->with('error', 'Order does not belong to this lead/brand.');
            }

            if ((int)$order->balance_due <= 0) {
                if ($seller) {
                    return redirect()
                        ->route('seller.orders.get')
                        ->with('info', 'Order is already fully paid.');
                }
                return redirect()
                    ->route('admin.orders.get')
                    ->with('info', 'Order is already fully paid.');
            }
        }

        if ($seller) {
            return view('sellers.pages.generate-payment-link', compact('brand', 'lead', 'order'));
        }
        return view('admin.pages.generate-payment-link', compact('brand', 'lead', 'order'));
    }

    public function renewOrderLink(Brand $brand, Lead $lead, ?Order $order = null, $type = null)
    {
        $seller  = auth('seller')->user();
        $admin   = auth('admin')->user();

        $orderType = $type;
        // dd($orderType);
        // ✅ Admin always allowed
        if ($admin) {
            $canGenerate = true;
        } else {
            // ✅ Only front sellers of this brand
            $canGenerate = $seller
                && (($seller->role ?? $seller->is_seller) === 'front_seller')
                && ((int)$seller->brand_id === (int)$brand->id);
        }
        // Lead must belong to brand
        abort_unless((int)$lead->brand_id === (int)$brand->id, 404, 'Lead/brand mismatch.');
        // Check if allowed
        abort_unless($canGenerate, 403, 'Only admins or front sellers of this brand can generate a link.');

        // If order is given, validate
        if ($order) {
            if (
                (int)$order->brand_id !== (int)$brand->id ||
                (int)$order->client_id !== (int)$lead->client_id
            ) {
                return redirect()
                    ->route('seller.orders.get')
                    ->with('error', 'Order does not belong to this lead/brand.');
            }
        }
        return view('sellers.pages.renew-payment-link', compact('brand', 'lead', 'order', 'orderType'));
    }

    protected function sellerOwnsLead(Seller $seller, Lead $lead, Brand $brand): bool
    {
        return (int) $seller->id === (int) $lead->seller_id
            && (int) $seller->brand_id === (int) $brand->id;
    }

    public function generatePayLink(Request $request, Brand $brand, Lead $lead, PaymentLinkService $links)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();

        // // Admin can always generate; seller must own lead and be in same brand
        // $canGenerate = $admin
        //     ? true
        //     : ($seller && ($seller->id === $lead->seller_id && $seller->brand_id === $brand->id));
        $canGenerate = $admin ? true : ($seller && $this->sellerOwnsLead($seller, $lead, $brand));

        abort_unless($canGenerate, 403, 'Seller must belong to and own the lead.');

        $data = $request->validate([
            'service_name'     => ['required', 'string', 'max:255'],
            'currency'         => ['required', 'string', 'size:3'],
            'total_amount'     => ['required', 'numeric', 'gt:0'],
            'payable_amount'   => ['required', 'numeric', 'gt:0'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'provider'         => ['nullable', 'in:stripe,paypal'],
            'order_type'       => ['nullable', 'in:original,renewal'],
            'parent_order_id'  => ['nullable', 'integer', 'exists:orders,id'], // for renewals
            'order'            => ['nullable', 'integer'], // you were passing as $request->order
        ]);

        if ((float)$data['payable_amount'] > (float)$data['total_amount']) {
            return back()->with('error', 'Payable amount cannot exceed total amount.');
        }

        $actor = $admin ?: $seller;
        abort_unless($actor, 403);

        $link = $links->createInstallmentLink(
            brand: $brand,
            lead: $lead,
            sellerIdWhoGenerated: $seller->id ?? $lead->seller_id,
            serviceName: $data['service_name'],
            currency: strtoupper($data['currency']),
            totalCents: (int) round($data['total_amount'] * 100),
            payNowCents: (int) round($data['payable_amount'] * 100),
            expiresInHours: $data['expires_in_hours'] ?? null,
            provider: $data['provider'] ?? null,
            orderType: $data['order_type'] ?? 'original',
            parentOrderId: (int) ($data['parent_order_id'] ?? $request->order ?? 0) ?: null,
            meta: [
                'generated_by_id'   => $actor->id,
                'generated_by_type' => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
            ],
        );

        $url = $link->signedUrl();
        $link->update([
            'last_issued_url'        => $url,
            'last_issued_at'         => now(),
            'last_issued_expires_at' => $link->expires_at ?? now()->addDays(7),
            'generated_by_id'        => $actor->id,
            'generated_by_type'      => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
        ]);

        Notification::route('mail', $link->client?->email ?? $lead->email)
            ->notify(new PaymentLinkNotification($link, $url));

        return back()->with('success', 'Payment link created.')->with('payment_link_url', $url);
    }
}
