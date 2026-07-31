<?php

namespace App\Http\Controllers\Seller;

use App\Models\Brand;
use App\Models\Order;
use App\Models\AccountKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;
use App\Support\TenantContext;
use App\Services\Tenant\TenantLimitService;

class SellerBrandController extends Controller
{
    public function __construct(
        private TenantLimitService $limits,
    ) {}

    public function sellerBrandPost(Request $request)
    {
        PortalAuthorization::requireAdmin();

        $validated = $request->validate([
            'brand_name' => 'required|string|max:255',
            'brand_url'  => 'required|url',
        ]);

        $tenantId = TenantContext::resolve();
        if ($tenantId) {
            $this->limits->assertCanCreateBrand('ppc', (int) $tenantId);
        }

        $brand = new Brand();
        $brand->brand_name = $validated['brand_name'];
        $brand->brand_url  = $validated['brand_url'];
        $brand->tenant_id  = TenantContext::resolve();
        $brand->save();

        return back()->with('success', 'Brand added successfully.');
    }

    public function sellerBrands()
    {
        $seller = auth('seller')->user();
        abort_unless($seller, 403, 'Unauthorized.');

        $brands = Brand::where('id', $seller->brand_id)->paginate(20);

        return view('sellers.pages.brands', compact('brands'));
    }

    public function sellerBrandPayments(Request $request)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();

        if ($seller) {
            abort(403, 'Sellers cannot access this page.');
        }

        if ($admin && in_array($admin->role, ['admin', 'finance', 'white_wolf'], true)) {
            return redirect()->route('admin.brand-payments.get');
        }

        return back()->with('error', 'You do not have permission to access this page.');
    }

    // public function sellerBrandPaymentsLegacy(Request $request)
    // {
    //     $seller = auth('seller')->user();
    //     $admin  = auth('admin')->user();

    //     // Permissions
    //     if (! $admin || ! in_array($admin->role, ['admin', 'finance'])) {
    //         return back()->with('error', 'You do not have permission to access this page.');
    //     }
    //     if ($seller) {
    //         abort(403, 'Sellers cannot access this page.');
    //     }
    //     // Per-order aggregation subquery:
    //     // snapshot = MAX(order_total_snapshot) per order
    //     // paid     = SUM(unit_amount) of PAID links per order
    //     $perOrderAgg = DB::table('payment_links')
    //         ->select('order_id')
    //         ->selectRaw('MAX(order_total_snapshot) AS snapshot')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) AS paid')
    //         ->groupBy('order_id');

    //     // Brand totals: keep ALL brands via LEFT JOIN chain
    //     $brandPayments = DB::table('brands')
    //         ->leftJoin('orders', 'orders.brand_id', '=', 'brands.id')
    //         ->leftJoinSub($perOrderAgg, 'op', function ($join) {
    //             $join->on('orders.id', '=', 'op.order_id');
    //         })
    //         ->select(
    //             'brands.id',
    //             'brands.brand_name',
    //             'brands.brand_url',
    //             DB::raw('COUNT(DISTINCT orders.id) AS orders_count'),
    //             DB::raw('COALESCE(SUM(op.paid), 0) AS total_paid'),
    //             DB::raw('COALESCE(SUM(op.snapshot), 0) AS total_snapshot')
    //         )
    //         ->groupBy('brands.id', 'brands.brand_name', 'brands.brand_url')
    //         ->get()
    //         ->map(function ($row) {
    //             $due = $row->total_snapshot - $row->total_paid;
    //             return [
    //                 'id'           => $row->id,
    //                 'brand_name'   => $row->brand_name,
    //                 'brand_url'    => $row->brand_url,
    //                 'orders_count' => (int) $row->orders_count,
    //                 'total_paid'   => (int) $row->total_paid,
    //                 'total_due'    => max((int) $due, 0),
    //             ];
    //         });

    //     // Orders list (for context)
    //     $orders = Order::with(['brand', 'client', 'seller'])
    //         ->latest('id')
    //         ->paginate(20)
    //         ->withQueryString();

    //     // Provider totals (OK to sum directly; equals sum of per-order paid anyway)
    //     $providerPayments = DB::table('payment_links')
    //         ->select('provider')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) AS total_paid')
    //         ->groupBy('provider')
    //         ->pluck('total_paid', 'provider');

    //     // Overall totals — avoid alias names in SUMs

    //     // 1) Sum of ALL paid link amounts (equals sum of op.paid)
    //     $paymentPaidAmount = (int) DB::table('payment_links')
    //         ->where('status', 'paid')
    //         ->sum('unit_amount');

    //     // 2) Sum of per-order snapshots = sum(MAX(snapshot) per order)
    //     $perOrderSnapshots = DB::table('payment_links')
    //         ->selectRaw('order_id, MAX(order_total_snapshot) AS snapshot')
    //         ->groupBy('order_id');

    //     $totalOrderSnapshot = (int) DB::table(DB::raw("({$perOrderSnapshots->toSql()}) AS t"))
    //         ->mergeBindings($perOrderSnapshots)
    //         ->sum('snapshot');

    //     $paymentDueAmount = $totalOrderSnapshot - $paymentPaidAmount;
    //     $revenue = $paymentPaidAmount;

    //     return view(
    //         'admin.pages.brand-payments',
    //         [
    //             'revenue'          => $revenue,
    //             'paymentPaid'      => $paymentPaidAmount,
    //             'paymentDue'       => max($paymentDueAmount, 0),
    //             'providerPayments' => $providerPayments,
    //             'orders'           => $orders,
    //             'brandPayments'    => $brandPayments,
    //         ]
    //     );
    // }

    // public function sellerBrandPayments(Request $request)
    // {
    //     $seller = auth('seller')->user();
    //     $admin  = auth('admin')->user();

    //     // 🚫 Permission check
    //     if (! $admin || ! in_array($admin->role, ['admin', 'finance'])) {
    //         return back()->with('error', 'You do not have permission to access this page.');
    //     }
    //     if ($seller) {
    //         abort(403, 'Sellers cannot access this page.');
    //     }

    //     // --- Brand totals from payment_links grouped per order ---
    //     $brandPayments = DB::table('brands')
    //         ->leftJoin('orders', 'orders.brand_id', '=', 'brands.id')
    //         ->leftJoin('payment_links', 'payment_links.order_id', '=', 'orders.id')
    //         ->select(
    //             'brands.id',
    //             'brands.brand_name',
    //             'brands.brand_url',
    //             DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
    //             // total paid (sum of paid payment links)
    //             DB::raw('COALESCE(SUM(CASE WHEN payment_links.status = "paid" THEN payment_links.unit_amount ELSE 0 END), 0) as total_paid'),
    //             // total snapshot: sum of distinct order snapshots
    //             DB::raw('COALESCE(SUM(DISTINCT payment_links.order_total_snapshot), 0) as total_snapshot')
    //         )
    //         ->groupBy('brands.id', 'brands.brand_name', 'brands.brand_url')
    //         ->get()
    //         ->map(function ($row) {
    //             $due = $row->total_snapshot - $row->total_paid;
    //             return [
    //                 'id'           => $row->id,
    //                 'brand_name'   => $row->brand_name,
    //                 'brand_url'    => $row->brand_url,
    //                 'orders_count' => $row->orders_count,
    //                 'total_paid'   => $row->total_paid,
    //                 'total_due'    => max($due, 0), // never negative
    //             ];
    //         });

    //     // --- Orders list ---
    //     $orders = Order::with(['brand', 'client', 'seller'])
    //         ->orderByDesc('id')
    //         ->paginate(20)
    //         ->withQueryString();
    //     // --- Fix payments (using payment_links grouped per order) ---
    //     $orderPayments = DB::table('payment_links')
    //         ->select('order_id')
    //         ->selectRaw('MAX(order_total_snapshot) as snapshot')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) as paid')
    //         ->groupBy('order_id')
    //         ->get();

    //     // --- Payments grouped by provider ---
    //     $providerPayments = DB::table('payment_links')
    //         ->select('provider')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) as total_paid')
    //         ->groupBy('provider')
    //         ->get()
    //         ->pluck('total_paid', 'provider');

    //     $paymentPaidAmount = $orderPayments->sum('paid');
    //     $totalOrderSnapshot = $orderPayments->sum('snapshot');
    //     $paymentDueAmount = $totalOrderSnapshot - $paymentPaidAmount;
    //     // Total revenue (optional, from orders table if you keep it)
    //     $revenue = $paymentPaidAmount; // 👈 use payments, not orders.amount_paid

    //     // dd($brandPayments, $orders, $orderPayments, $providerPayments);
    //     return view('sellers.pages.brand-payments', [
    //         'revenue'      => $revenue,
    //         'paymentPaid'  => $paymentPaidAmount,
    //         'paymentDue'   => $paymentDueAmount,
    //         'providerPayments'   => $providerPayments,
    //     ], compact('orders', 'brandPayments'));
    // }

    public function sellerBrandPayouts()
    {
        $admin = auth('admin')->user();

        if ($admin && in_array($admin->role, ['admin', 'finance', 'white_wolf'], true)) {
            return redirect()->route('admin.brand-payouts.get');
        }

        abort(403, 'Sellers cannot access payout reports.');
    }
}
