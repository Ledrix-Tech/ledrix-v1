<?php

namespace App\Http\Controllers\Seller;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;

class SellerOrderController extends Controller
{
    /**
     * Display seller’s orders (brand-based)
     */
    public function sellerOrders(Request $request)
    {
        $seller = auth('seller')->user();
        $isAdmin = auth('admin')->check();
        if (!$seller) {
            return redirect()->route('seller.login.get')->with('error', 'You must be logged in.');
        }

        $query = Order::with([
            'brand:id,brand_name',
            'client:id,name,email',
            'lead:id,brand_id,seller_id',
            'seller:id,name,email,sudo_name,brand_id',
            'latestPaymentLink:id,order_id,is_active_link,last_issued_url,status'
        ]);

        // --- Visibility rules ---
        if ($seller) {
            $role = $seller->role ?? $seller->is_seller;

            if ($role === 'front_seller') {
                // Front sellers → all brand orders
                $query->where('brand_id', $seller->brand_id);
            } else {
                // Project managers → only their own orders
                $query->where('brand_id', $seller->brand_id)
                    ->where('seller_id', $seller->id);
            }
        }

        // --- Filters ---
        $search = trim((string) ($request->input('q') ?: $request->input('search') ?: ''));

        $query
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('brand_id'), fn($q) => $q->where('brand_id', (int) $request->brand_id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(
                    fn($w) =>
                    $w->where('service_name', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_email', 'like', "%{$search}%")
                );
            });

        $orders = $query->where('order_type', 'original')->paginate(20)->withQueryString();

        return view('sellers.pages.orders', compact('orders'));
    }


    public function sellerOrderRenewals(Request $request, Order $order)
    {
        $seller = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (! $seller && ! $isAdmin) {
            return redirect()->route('seller.login.get')
                ->with('error', 'You must be logged in.');
        }

        PortalAuthorization::requirePortalActor();
        PortalAuthorization::authorizeOrder($order);

        $order->load([
            'brand:id,brand_name',
            'client:id,name,email',
            'seller:id,name,email,sudo_name,brand_id',
            'latestPaymentLink:id,order_id,is_active_link,last_issued_url',
        ]);

        $renewals = Order::with([
            'brand:id,brand_name',
            'client:id,name,email',
            'seller:id,name,email,sudo_name,brand_id',
            'latestPaymentLink:id,order_id,is_active_link,last_issued_url',
        ])
            ->where('parent_order_id', $order->id)
            ->when(trim((string) ($request->input('q') ?: $request->input('search') ?: '')) !== '', function ($q) use ($request) {
                $term = trim((string) ($request->input('q') ?: $request->input('search')));
                $q->where(function ($w) use ($term) {
                    $w->where('service_name', 'like', "%{$term}%")
                        ->orWhere('buyer_name', 'like', "%{$term}%")
                        ->orWhere('buyer_email', 'like', "%{$term}%")
                        ->orWhere('status', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('sellers.pages.renewed-orders', compact('order', 'renewals'));
    }

    // /**
    //  * Client’s renewed orders view
    //  */
    // public function sellerClientRenewedOrders(Request $request, int $clientId)
    // {
    //     $seller = auth('seller')->user();
    //     $isAdmin = auth('admin')->check();

    //     if (!$seller && !$isAdmin) {
    //         return redirect()->route('seller.login.get')->with('error', 'You must be logged in.');
    //     }

    //     $query = Order::with([
    //         'brand:id,brand_name',
    //         'client:id,name,email',
    //         'seller:id,name,email,sudo_name,brand_id',
    //         'latestPaymentLink:id,order_id,is_active_link,last_issued_url'
    //     ])
    //     ->where('client_id', $clientId)
    //     ->orderByDesc('created_at');

    //     // --- Visibility rules ---
    //     if ($seller) {
    //         $role = $seller->role ?? $seller->is_seller;
    //         $query->where('brand_id', $seller->brand_id);

    //         if ($role !== 'front_seller') {
    //             $query->where('seller_id', $seller->id);
    //         }
    //     }

    //     $orders = $query->get();
    //     $originalOrders = $orders->whereNull('parent_order_id');
    //     $renewalOrders  = $orders->where('order_type', 'renewal');

    //     return view('sellers.pages.renewed-orders', compact('originalOrders', 'renewalOrders'));
    // }

    /**
     * Project Manager’s Orders
     */
    public function sellerPMOrders(Request $request)
    {
        $seller = auth('seller')->user();
        if (!$seller) {
            return redirect()->route('seller.login.get')
                ->with('error', 'You must be logged in.');
        }

        $query = Order::with([
            'brand:id,brand_name',
            'client:id,name,email',
            'seller:id,name,email,sudo_name,brand_id',
            'latestPaymentLink:id,order_id,is_active_link,last_issued_url'
        ]);

        $role = $seller->role ?? $seller->is_seller;

        // ===== VISIBILITY RULES =====
        if (isProjectManager()) {
            // PM sees orders where the LEAD is assigned to them
            $query->whereIn('lead_id', function ($q) use ($seller) {
                $q->select('lead_id')
                    ->from('lead_assignments')
                    ->where('assigned_to', $seller->id);
            });
        } elseif (isFrontSeller()) {
            // FS sees all brand orders
            $query->where('brand_id', $seller->brand_id);
        } else {
            // Regular seller sees ONLY their orders
            $query->where('seller_id', $seller->id)
                ->where('brand_id', $seller->brand_id);
        }

        // ===== SEARCH FILTERS =====
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int)$request->brand_id);
        }

        if ($request->filled('q') || $request->filled('search')) {
            $term = trim((string) ($request->input('q') ?: $request->input('search')));
            $query->where(function ($w) use ($term) {
                $w->where('service_name', 'like', "%{$term}%")
                    ->orWhere('buyer_name', 'like', "%{$term}%")
                    ->orWhere('buyer_email', 'like', "%{$term}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        // dd($orders);

        return view('sellers.pages.pm-orders', compact('orders'));
    }

    /**
     * Seller Payment Records
     */
    public function sellerPayments(Request $request)
    {
        $seller = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (!$seller && !$isAdmin) {
            return redirect()->route('seller.login.get')->with('error', 'You must be logged in.');
        }

        $query = Order::with([
            'brand:id,brand_name',
            'client:id,name,email',
            'seller:id,name,email,sudo_name,brand_id',
        ]);

        // Visibility rules
        if ($seller) {
            $role = $seller->role ?? $seller->is_seller;
            if ($role === 'front_seller') {
                $query->where('brand_id', $seller->brand_id);
            } else {
                $query->where('brand_id', $seller->brand_id)
                    ->where('seller_id', $seller->id);
            }
        }

        // Filters
        if ($request->filled('status'))   $query->where('status', $request->string('status'));
        if ($request->filled('brand_id')) $query->where('brand_id', (int)$request->brand_id);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('service_name', 'like', "%{$q}%")
                    ->orWhere('buyer_name', 'like', "%{$q}%")
                    ->orWhere('buyer_email', 'like', "%{$q}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('sellers.pages.payment-data', compact('orders'));
    }
}
