<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Payment;
use App\Models\AccountKey;
use App\Models\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AdminBrandsController extends Controller
{
    public function adminBrandPost(Request $request)
    {
        try {
            // Validate incoming data
            $validated = $request->validate([
                'brand_name' => 'required|string|max:255',
                'brand_url'  => 'required|url|unique:brands,brand_url', // Ensure brand URL is unique
                'module'     => 'required|in:ppc,upwork', // Ensure module is either 'ppc' or 'upwork'
            ]);

            // Create and save brand
            $brand = new Brand();
            $brand->module = $validated['module'];
            $brand->brand_name = $validated['brand_name'];
            $brand->brand_url = $validated['brand_url'];  // Fix the repeated line here
            $brand->save();

            // Success message
            return back()->with('success', 'Brand added successfully.');
        } catch (\Exception $e) {
            // Log the exception and return an error message
            Log::error('Error creating brand', ['error' => $e->getMessage(), 'request' => $request->all()]);

            return back()->with('error', 'There was an issue adding the brand. Please try again later.');
        }
    }


    public function adminBrands()
    {
        $brands = Brand::paginate(20);
        return view('admin.pages.brands', compact('brands'));
    }

    public function adminDomainScript()
    {
        $brands = Brand::paginate(20);
        return view('admin.pages.domain-script', compact('brands'));
    }

    public function adminBrandPayments(Request $request)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();

        // 🚫 Sellers are not allowed here
        if ($seller) {
            abort(403, 'Sellers cannot access this page.');
        }

        // ✅ Only allowed admin roles
        if (!$admin || !in_array($admin->role, ['admin', 'finance', 'white_wolf'])) {
            return back()->with('error', 'You do not have permission to access this page.');
        }

        $grossCents = (int) Payment::sum('amount');
        $refundCents = (int) Payment::sum('refunded_amount');
        $chargebackCents = (int) Payment::where('refund_status', 'chargeback')->sum('amount');

        $netCents = max(0, $grossCents - ($refundCents + $chargebackCents));

        $rawProviderStats = Payment::select(
            'provider',
            DB::raw('SUM(amount) as gross'),
            DB::raw('SUM(refunded_amount) as refunds'),
            DB::raw('SUM(CASE WHEN refund_status = "chargeback" THEN amount ELSE 0 END) as chargebacks')
        )
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        $providerStats = [
            'stripe' => [
                'provider'    => 'stripe',
                'gross'       => (int) ($rawProviderStats['stripe']->gross ?? 0),
                'refunds'     => (int) ($rawProviderStats['stripe']->refunds ?? 0),
                'chargebacks' => (int) ($rawProviderStats['stripe']->chargebacks ?? 0),
                'net'         => max(
                    0,
                    (int) ($rawProviderStats['stripe']->gross ?? 0)
                        - ((int) ($rawProviderStats['stripe']->refunds ?? 0)
                            + (int) ($rawProviderStats['stripe']->chargebacks ?? 0))
                ),
            ],

            'paypal' => [
                'provider'    => 'paypal',
                'gross'       => (int) ($rawProviderStats['paypal']->gross ?? 0),
                'refunds'     => (int) ($rawProviderStats['paypal']->refunds ?? 0),
                'chargebacks' => (int) ($rawProviderStats['paypal']->chargebacks ?? 0),
                'net'         => max(
                    0,
                    (int) ($rawProviderStats['paypal']->gross ?? 0)
                        - ((int) ($rawProviderStats['paypal']->refunds ?? 0)
                            + (int) ($rawProviderStats['paypal']->chargebacks ?? 0))
                ),
            ],
        ];

        $pipelineRaw = PaymentLink::select(
            'provider',
            DB::raw('SUM(CASE WHEN status != "paid" THEN unit_amount ELSE 0 END) as pipeline')
        )
            ->groupBy('provider')
            ->pluck('pipeline', 'provider')
            ->toArray();

        $providerPipeline = [
            'stripe' => (int) ($pipelineRaw['stripe'] ?? 0),
            'paypal' => (int) ($pipelineRaw['paypal'] ?? 0),
        ];

        $perOrderAgg = DB::table('payment_links')
            ->select('order_id')
            ->selectRaw('MAX(order_total_snapshot) AS snapshot')
            ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) AS paid')
            ->groupBy('order_id');

        $brandPayments = DB::table('brands')
            ->leftJoin('orders', 'orders.brand_id', '=', 'brands.id')
            ->leftJoinSub(
                $perOrderAgg,
                'op',
                fn($join) =>
                $join->on('orders.id', '=', 'op.order_id')
            )
            ->select(
                'brands.id',
                'brands.brand_name',
                'brands.brand_url',
                DB::raw('COUNT(DISTINCT orders.id) AS orders_count'),

                // NEW:
                DB::raw('COUNT(DISTINCT CASE WHEN orders.order_type = "original" THEN orders.id END) AS original_orders'),
                DB::raw('COUNT(DISTINCT CASE WHEN orders.order_type = "renewal" THEN orders.id END) AS renewal_orders'),

                DB::raw('COALESCE(SUM(op.paid), 0) AS total_paid'),
                DB::raw('COALESCE(SUM(op.snapshot), 0) AS total_snapshot')
            )
            ->groupBy('brands.id', 'brands.brand_name', 'brands.brand_url')
            ->get()
            ->map(function ($row) {
                $due = (int) $row->total_snapshot - (int) $row->total_paid;

                return [
                    'id'             => $row->id,
                    'brand_name'     => $row->brand_name,
                    'brand_url'      => $row->brand_url,

                    'orders_count'   => (int) $row->orders_count,
                    'original_orders' => (int) $row->original_orders,
                    'renewal_orders' => (int) $row->renewal_orders,

                    'total_paid'     => (int) $row->total_paid,
                    'total_due'      => max($due, 0),
                ];
            });

        // $brandPayments = DB::table('brands')
        //     ->leftJoin('orders', 'orders.brand_id', '=', 'brands.id')
        //     ->leftJoinSub($perOrderAgg, 'op', fn($join) => $join->on('orders.id', '=', 'op.order_id'))
        //     ->select(
        //         'brands.id',
        //         'brands.brand_name',
        //         'brands.brand_url',
        //         DB::raw('COUNT(DISTINCT orders.id) AS orders_count'),
        //         DB::raw('COALESCE(SUM(op.paid), 0) AS total_paid'),
        //         DB::raw('COALESCE(SUM(op.snapshot), 0) AS total_snapshot')
        //     )
        //     ->groupBy('brands.id', 'brands.brand_name', 'brands.brand_url')
        //     ->get()
        //     ->map(function ($row) {
        //         $due = (int) $row->total_snapshot - (int) $row->total_paid;
        //         return [
        //             'id'           => $row->id,
        //             'brand_name'   => $row->brand_name,
        //             'brand_url'    => $row->brand_url,
        //             'orders_count' => (int) $row->orders_count,
        //             'total_paid'   => (int) $row->total_paid,
        //             'total_due'    => max($due, 0),
        //         ];
        //     });

        $orders = Order::with(['brand', 'client', 'seller'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // forcast revenue for months
        // forcast revenue for months (with correct brand_id association)
        $brandMonthly = Payment::selectRaw("
        orders.brand_id,
        DATE_FORMAT(payments.created_at, '%Y-%m') AS month,
        SUM(payments.amount - payments.refunded_amount) AS net
    ")
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->groupBy('orders.brand_id', 'month')
            ->get()
            ->groupBy('orders.brand_id');
        $brandForecasts = [];
        foreach ($brandMonthly as $brandId => $rows) {
            $values = collect($rows)
                ->sortByDesc('month')
                ->take(3)
                ->pluck('net')
                ->values();

            $weights = collect([3, 2, 1])->take($values->count());

            $forecast = $values->count() > 0
                ? round($values->zip($weights)->sum(fn($pair) => $pair[0] * $pair[1]) / $weights->sum(), 2)
                : 0;

            $brandForecasts[$brandId] = (int) $forecast; // in cents
        }
        // pipeline for future
        $brandPipeline = PaymentLink::select('order_id')
            ->selectRaw('SUM(CASE WHEN status != "paid" THEN unit_amount ELSE 0 END) as pipeline')
            ->groupBy('order_id')
            ->pluck('pipeline', 'order_id')
            ->toArray();
        // provider future revneu
        $providerForecast = Payment::select(
            'provider',
            DB::raw('SUM(amount - refunded_amount) AS net'),
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS month")
        )
            ->groupBy('provider', 'month')
            ->get()
            ->groupBy('provider');

        $providerForecastFormatted = [];
        foreach ($providerForecast as $provider => $rows) {

            $values = $rows->sortByDesc('month')
                ->take(3)
                ->pluck('net')
                ->values();

            $weights = collect([3, 2, 1])->take($values->count());

            $forecast = $values->count() > 0
                ? round($values->zip($weights)->sum(fn($pair) => $pair[0] * $pair[1]) / $weights->sum(), 2)
                : 0;

            $providerForecastFormatted[$provider] = (int) $forecast;
        }

        // dd([
        //     // Provider analytics
        //     'providerStats'      => $providerStats,
        //     'providerPipeline'   => $providerPipeline,
        //     'providerForecast'   => $providerForecastFormatted, // NEW
        // ]);

        return view('admin.pages.brand-payments', [
            // Global revenues
            'gross_revenue'     => $grossCents,
            'net_revenue'       => $netCents,
            'refunds'           => $refundCents,
            'chargebacks'       => $chargebackCents,

            // Provider analytics
            'providerStats'     => $providerStats,
            'providerPipeline'  => $providerPipeline,
            'providerForecast'  => $providerForecastFormatted, // NEW

            // Brand analytics
            'brandPayments'     => $brandPayments,
            'brandForecasts'    => $brandForecasts,    // NEW
            'brandPipeline'     => $brandPipeline,     // NEW

            // Orders table
            'orders'            => $orders,
        ]);
    }

    // optimize calculation for brand payouts
    // public function adminBrandPayments(Request $request)
    // {
    //     $seller = auth('seller')->user();
    //     $admin  = auth('admin')->user();

    //     // 🚫 Restrict sellers first
    //     if ($seller) {
    //         abort(403, 'Sellers cannot access this page.');
    //     }

    //     // ✅ Only allow specific admin roles
    //     if (! $admin || ! in_array($admin->role, ['admin', 'finance', 'white_wolf'])) {
    //         return back()->with('error', 'You do not have permission to access this page.');
    //     }

    //     // ---- everything below unchanged ----
    //     $perOrderAgg = DB::table('payment_links')
    //         ->select('order_id')
    //         ->selectRaw('MAX(order_total_snapshot) AS snapshot')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) AS paid')
    //         ->groupBy('order_id');

    //     $brandPayments = DB::table('brands')
    //         ->leftJoin('orders', 'orders.brand_id', '=', 'brands.id')
    //         ->leftJoinSub($perOrderAgg, 'op', fn($join) => $join->on('orders.id', '=', 'op.order_id'))
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

    //     $orders = Order::with(['brand', 'client', 'seller'])
    //         ->latest('id')
    //         ->paginate(20)
    //         ->withQueryString();

    //     $providerPayments = DB::table('payment_links')
    //         ->select('provider')
    //         ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) AS total_paid')
    //         ->groupBy('provider')
    //         ->pluck('total_paid', 'provider');

    //     $paymentPaidAmount = (int) DB::table('payment_links')
    //         ->where('status', 'paid')
    //         ->sum('unit_amount');

    //     $perOrderSnapshots = DB::table('payment_links')
    //         ->selectRaw('order_id, MAX(order_total_snapshot) AS snapshot')
    //         ->groupBy('order_id');

    //     $totalOrderSnapshot = (int) DB::table(DB::raw("({$perOrderSnapshots->toSql()}) AS t"))
    //         ->mergeBindings($perOrderSnapshots)
    //         ->sum('snapshot');

    //     $paymentDueAmount = $totalOrderSnapshot - $paymentPaidAmount;
    //     $revenue = $paymentPaidAmount;

    //     return view('admin.pages.brand-payments', [
    //         'revenue'          => $revenue,
    //         'paymentPaid'      => $paymentPaidAmount,
    //         'paymentDue'       => max($paymentDueAmount, 0),
    //         'providerPayments' => $providerPayments,
    //         'orders'           => $orders,
    //         'brandPayments'    => $brandPayments,
    //     ]);
    // }

    public function adminBrandPayouts()
    {
        $brands = Brand::all();
        $brandData = [];

        foreach ($brands as $brand) {
            $keys = AccountKey::where('brand_id', $brand->id)->first();
            if (!$keys || !$keys->stripe_secret_key) {
                $brandData[] = [
                    'brand' => $brand,
                    'payouts' => [],
                ];
                continue;
            }

            \Stripe\Stripe::setApiKey($keys->stripe_secret_key);

            try {
                $payoutList = \Stripe\Payout::all([
                    'limit' => 5,
                    // you can pass filters like status, created, arrival_date
                ]);

                $payouts = $payoutList->data;

                // Optionally for each payout, fetch the associated balance transactions
                foreach ($payouts as &$payout) {
                    if (isset($payout->balance_transaction)) {
                        $txn = \Stripe\BalanceTransaction::retrieve($payout->balance_transaction);
                        $payout->balance_details = $txn;
                    }
                }
            } catch (\Exception $e) {
                // Log error
                Log::error("Stripe: payout fetch failed for brand {$brand->id}", [
                    'error' => $e->getMessage(),
                ]);
                $payouts = [];
            }

            $brandData[] = [
                'brand' => $brand,
                'payouts' => $payouts,
            ];
        }
        // dd($brandData);
        return view('admin.pages.eachBrand-payouts', compact('brandData'));
    }
}
