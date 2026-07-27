<?php

namespace App\Http\Controllers\Seller;

use App\Models\Lead;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Seller;
use App\Models\Payment;
use App\Models\RiskyClient;
use Illuminate\Http\Request;
use App\Models\LeadAssignment;
use Illuminate\Validation\Rule;
use App\Models\PerformanceBonus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SellerPerformance;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;
use App\Support\TenantContext;
use App\Services\Tenant\TenantLimitService;

class SellerDataController extends Controller
{
    /**
     * Show all sellers (for current seller’s brand)
     */
    public function sellerSellers()
    {
        $seller = auth('seller')->user();
        abort_unless($seller, 403, 'Unauthorized');

        // Show sellers of same brand (no company check)
        $sellers = Seller::with('brand:id,brand_name')
            ->where('brand_id', $seller->brand_id)
            ->paginate(20);

        $brands = Brand::where('id', $seller->brand_id)->get();

        return view('sellers.pages.executives', compact('sellers', 'brands'));
    }

    /**
     * Add new seller (under same brand)
     */
    public function sellerSellerPost(Request $request)
    {
        $actor = PortalAuthorization::requireFrontSellerOrAdmin();

        $validated = $request->validate([
            'brand_id'  => 'required|exists:brands,id',
            'sudo_name' => 'required|string|max:255',
            'name'      => 'required|string|max:255',
            'is_seller' => ['required', Rule::in(['project_manager', 'front_seller'])],
            'email'     => 'required|email|unique:sellers,email',
            'password'  => 'required|min:8',
        ]);

        if ($actor instanceof Seller) {
            abort_unless((int) $validated['brand_id'] === (int) $actor->brand_id, 403, 'You can only add sellers to your brand.');
        }

        $tenantId = TenantContext::resolve() ?? Brand::find($validated['brand_id'])?->tenant_id;
        if ($tenantId) {
            app(TenantLimitService::class)->assertCanCreateSeller((int) $tenantId);
        }

        $seller = new Seller();
        $seller->brand_id   = $validated['brand_id'];
        $seller->tenant_id  = TenantContext::resolve() ?? Brand::find($validated['brand_id'])?->tenant_id;
        $seller->sudo_name  = $validated['sudo_name'];
        $seller->is_seller  = $validated['is_seller'];
        $seller->name       = $validated['name'];
        $seller->email      = $validated['email'];
        $seller->password   = bcrypt($validated['password']);
        $seller->save();

        return back()->with('success', 'Seller added successfully.');
    }

    /**
     * Update seller status (Active/Inactive)
     */
    public function sellerUpdateStatus(Request $request)
    {
        PortalAuthorization::requireFrontSellerOrAdmin();

        $request->validate([
            'user_id' => 'required|integer|exists:sellers,id',
            'status'  => 'required|in:Active,Inactive',
        ]);

        $seller = Seller::findOrFail($request->user_id);
        PortalAuthorization::authorizeSameBrandSeller($seller);

        $seller->status = $request->status;
        $seller->save();

        return response()->json(['success' => true]);
    }

    public function sellerSellerPerformance(?int $seller = null)
    {
        $authSeller = auth('seller')->user();
        $targetId = $seller ?? $authSeller?->id;

        abort_unless($targetId, 404, 'Seller not found.');

        if ($authSeller && (int) $authSeller->id !== (int) $targetId) {
            abort(403, 'You can only view your own performance.');
        }

        $data = SellerPerformance::build($targetId);

        return view('sellers.pages.executive-performance', $data);
    }

    /** Legacy URL: /seller/seller/{id}/performance */
    public function sellerSellerPerformanceLegacy(int $seller)
    {
        return redirect()->route('seller.seller-performance.get', $seller);
    }

    // // old performance code seller
    // public function sellerSellerPerformance(int $id)
    // {
    //     $admin = auth('admin')->user();
    //     $seller = auth('seller')->user();

    //     // 🧠 Allow admins to see all sellers
    //     if (isAdmin()) {
    //         $targetSeller = Seller::findOrFail($id);
    //     }
    //     // 🧠 Sellers can only see their own performance
    //     elseif ($seller) {
    //         abort_unless($seller->id == $id, 403, 'Unauthorized access.');
    //         $targetSeller = $seller;
    //     } else {
    //         return redirect()->route('admin.login.get')->with('error', 'Unauthorized.');
    //     }

    //     $seller = Seller::with('brand')->findOrFail($id);


    //     // old All payments credited to THIS seller
    //     $creditedPayments = Payment::with(['order.lead.client', 'paymentLink'])
    //         ->where('credit_to_seller_id', $seller->id)
    //         ->latest('created_at')
    //         ->get();

    //     // new added charge disputed
    //     $grossCents      = (int) $creditedPayments->sum('amount');               // before refunds
    //     $refundCents     = (int) $creditedPayments->sum('refunded_amount');      // only refunded
    //     $chargebackCents = (int) $creditedPayments
    //         ->where('refund_status', 'chargeback')
    //         ->sum('amount');                                  // full chargeback
    //     $netCents = $grossCents - ($refundCents + $chargebackCents);
    //     $netRevenue = $netCents / 100;

    //     // old cents calculate
    //     // $revenueCents = (int) $creditedPayments->sum('amount');
    //     // $revenue      = $revenueCents / 100;

    //     // charge back calculations
    //     $revenueCents = $grossCents;   // rename for clarity
    //     $revenue      = $grossCents / 100;
    //     $refunds      = $refundCents / 100;
    //     $chargebacks  = $chargebackCents / 100;

    //     // monthly revenue with refund and charge backs
    //     $monthlyIncome = $creditedPayments
    //         ->groupBy(fn($p) => $p->created_at->format('Y-m'))
    //         ->map(function ($group) {
    //             $gross = $group->sum('amount');
    //             $refund = $group->sum('refunded_amount');
    //             $chargeback = $group->where('refund_status', 'chargeback')->sum('amount');

    //             return ($gross - ($refund + $chargeback)) / 100; // net
    //         });

    //     // old Monthly revenue from credited payments
    //     // $monthlyIncome = $creditedPayments
    //     //     ->groupBy(fn($p) => \Carbon\Carbon::parse($p->created_at)->format('Y-m'))
    //     //     ->map(fn($g) => (int) $g->sum('amount') / 100); // dollars

    //     $months = $monthlyIncome->keys()->values()->all();
    //     $totals = $monthlyIncome->values()->all();
    //     $currentMonth   = now()->format('Y-m');
    //     $previousMonth  = now()->subMonth()->format('Y-m');
    //     $currentRevenue = $monthlyIncome[$currentMonth]  ?? 0;
    //     $previousRevenue = $monthlyIncome[$previousMonth] ?? 0;
    //     // $growth = $previousRevenue > 0
    //     //     ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
    //     //     : null;
    //     if ($previousRevenue > 0) {
    //         $growth = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
    //     } elseif ($currentRevenue > 0) {
    //         $growth = 100; // or maybe "∞" (infinite growth) since it's from 0 → some revenue
    //     } else {
    //         $growth = 0; // both months zero
    //     }

    //     // --------------------------------------------------
    //     // 4️⃣  PIPELINE (unpaid links)
    //     // --------------------------------------------------
    //     $pipelineAmount = (float) DB::table('payment_links')
    //         ->when(
    //             $seller->is_seller === 'front_seller',
    //             fn($q) =>
    //             $q->where('credit_to_seller_id', $seller->id)
    //         )
    //         ->when(
    //             $seller->is_seller === 'project_manager',
    //             fn($q) =>
    //             $q->where('owner_seller_id', $seller->id)
    //         )
    //         ->where('status', '!=', 'paid')
    //         ->sum('unit_amount') / 100;

    //     // --------------------------------------------------
    //     // 5️⃣  FORECAST (last 3 months weighted)
    //     // --------------------------------------------------
    //     $values = collect($monthlyIncome)->sortKeysDesc()->take(3)->values();
    //     $weights = collect([3, 2, 1])->take($values->count());
    //     $forecastNextMonth = $values->count() > 0
    //         ? round($values->zip($weights)->sum(fn($pair) => $pair[0] * $pair[1]) / $weights->sum(), 2)
    //         : 0;

    //     // --------------------------------------------------
    //     // 6️⃣  BONUS RULES
    //     // --------------------------------------------------
    //     $currentMonthStart = now()->startOfMonth();
    //     $currentMonthEnd   = now()->endOfMonth();

    //     $bonusRule = PerformanceBonus::where('seller_id', $seller->id)
    //         ->where(function ($q) use ($currentMonthStart, $currentMonthEnd) {
    //             $q->whereNull('period_start')
    //                 ->orWhere(function ($q2) use ($currentMonthStart, $currentMonthEnd) {
    //                     $q2->where('period_start', '<=', $currentMonthEnd)
    //                         ->where(function ($q3) use ($currentMonthStart) {
    //                             $q3->whereNull('period_end')
    //                                 ->orWhere('period_end', '>=', $currentMonthStart);
    //                         });
    //                 });
    //         })
    //         ->first();

    //     $bonusEarned = 0;
    //     $bonusProgress = 0;
    //     if ($bonusRule) {
    //         $target = (float) $bonusRule->target_revenue;
    //         $bonusProgress = $target > 0 ? round(min(($revenue / $target) * 100, 100), 2) : 0;
    //         if ($revenue >= $target) {
    //             $bonusEarned = $bonusRule->bonus_amount;
    //         }
    //     }

    //     //  Orders & leads that contributed to the above payments
    //     $orderIds = $creditedPayments->pluck('order_id')->unique()->values();
    //     $orders = Order::with(['payments' => function ($q) use ($seller) {
    //         $q->where('credit_to_seller_id', $seller->id);
    //     }, 'lead.client'])
    //         ->whereIn('id', $orderIds)
    //         ->get();
    //     $totalOrders  = $orders->count();
    //     $paidOrders   = $orders->where('status', 'paid')->count();
    //     $unpaidOrders = $totalOrders - $paidOrders;
    //     $totalDueCents = (int) $orders->sum('balance_due');
    //     $totalDue      = $totalDueCents / 100;
    //     // Leads connected to those orders (context list)
    //     $leadIds = $orders->pluck('lead_id')->filter()->unique();
    //     $leadsInvolved = Lead::with('client')->whereIn('id', $leadIds)->get();
    //     //    Front sellers → leads where seller_id = $id
    //     //    PMs           → assignments where assigned_to = $id
    //     $isFront = $seller->is_seller === 'front_seller';
    //     if ($isFront) {
    //         $leadBaseQuery = Lead::where('seller_id', $seller->id);
    //     } else {
    //         // if you keep a LeadAssignment table
    //         $assignedLeadIds = LeadAssignment::where('assigned_to', $seller->id)
    //             ->pluck('lead_id');
    //         $leadBaseQuery = Lead::whereIn('id', $assignedLeadIds);
    //     }
    //     $totalLeads = (clone $leadBaseQuery)->count();
    //     $leadStatuses = (clone $leadBaseQuery)
    //         ->select('status', DB::raw('COUNT(*) as c'))
    //         ->groupBy('status')
    //         ->pluck('c', 'status')
    //         ->toArray();
    //     $convertedLeadIds = $orders->pluck('lead_id')->unique()->filter();
    //     $convertedLeads   = $convertedLeadIds->count();

    //     // new performance chek with cahrgeback calculates
    //     $performance = [
    //         'total_leads'     => $totalLeads,
    //         'total_orders'    => $totalOrders,
    //         'paid_orders'     => $paidOrders,
    //         'unpaid_orders'   => $unpaidOrders,

    //         // Revenue
    //         'gross_revenue'   => $revenue,           // before refunds
    //         'refunds'         => $refunds,
    //         'chargebacks'     => $chargebacks,
    //         'net_revenue'     => $netRevenue,        // after refund deductions

    //         // Other KPIs
    //         'total_due'       => $totalDue,
    //         'conversion_rate' => $totalLeads > 0
    //             ? round(($convertedLeads / $totalLeads) * 100, 2)
    //             : 0,
    //         'avg_order_value' => $totalOrders > 0 ? round($netRevenue / max(1, $totalOrders), 2) : 0,
    //         'monthly_growth'  => $growth,
    //         'pipeline_amount'     => $pipelineAmount,
    //         'forecast_next_month' => $forecastNextMonth,

    //         // Bonus Rules
    //         'bonus_rule_target' => $bonusRule?->target_revenue,
    //         'bonus_rule_amount' => $bonusRule?->bonus_amount,
    //         'bonus_earned'      => $bonusEarned,
    //         'bonus_progress'    => $bonusProgress,

    //         'pipeline_ratio' => $netRevenue > 0 ? round(($pipelineAmount / $netRevenue) * 100, 2) : 0,
    //     ];


    //     // old performance Build performance block (revenue KPIs are by CREDIT)
    //     // $performance = [
    //     //     'total_leads'     => $totalLeads,            // volume KPI for context
    //     //     'total_orders'    => $totalOrders,           // orders that yielded credited payments
    //     //     'paid_orders'     => $paidOrders,
    //     //     'unpaid_orders'   => $unpaidOrders,
    //     //     'total_revenue'   => $revenue,               // credited revenue
    //     //     'total_due'       => $totalDue,              // due on those orders (context)
    //     //     // 'conversion_rate' => $totalLeads > 0 ? round(($paidOrders / $totalLeads) * 100, 2) : 0,
    //     //     'conversion_rate' => $totalLeads > 0
    //     //         ? round(($convertedLeads / $totalLeads) * 100, 2)
    //     //         : 0,
    //     //     'avg_order_value' => $totalOrders > 0 ? round($revenue / max(1, $totalOrders), 2) : 0,
    //     //     'monthly_growth'  => $growth,
    //     //     'pipeline_amount'      => $pipelineAmount,
    //     //     'forecast_next_month'  => $forecastNextMonth,
    //     //     'bonus_rule_target'    => $bonusRule?->target_revenue,
    //     //     'bonus_rule_amount'    => $bonusRule?->bonus_amount,
    //     //     'bonus_earned'         => $bonusEarned,
    //     //     'bonus_progress'       => $bonusProgress,
    //     //     'pipeline_ratio'       => $revenue > 0 ? round(($pipelineAmount / $revenue) * 100, 2) : 0,
    //     // ];

    //     // Risky clients that belong to this seller
    //     $riskyClients = RiskyClient::with([
    //         'client.orders' => fn($q) => $q->latest()->take(10),
    //         'client.orders.payments',
    //     ])
    //         ->whereHas('client.leads', function ($q) use ($seller) {
    //             if ($seller->is_seller === 'front_seller') {
    //                 // Direct link (front seller owns the lead)
    //                 $q->where('seller_id', $seller->id);
    //             } else {
    //                 // PM link (via lead assignments table)
    //                 $assignedLeadIds = LeadAssignment::where('assigned_to', $seller->id)
    //                     ->pluck('lead_id');
    //                 $q->whereIn('id', $assignedLeadIds);
    //             }
    //         })
    //         ->orderByDesc('risk_score')
    //         ->get();
    //     // “Orders by client” list restricted to credited orders
    //     $clientsWithOrders = $orders
    //         ->groupBy(fn($o) => $o->lead?->client?->id)
    //         ->map(function ($group) {
    //             $client = optional($group->first()->lead)->client;
    //             return [
    //                 'client' => $client,
    //                 'orders' => $group->values(),
    //                 'last_payment' => $group->flatMap->payments
    //                     ->sortByDesc('created_at')
    //                     ->first(),
    //             ];
    //         })
    //         ->values();
    //     // dd($creditedPayments, $revenueCents, $revenue, $monthlyIncome, $totalLeads, $leadStatuses, $riskyClients, $clientsWithOrders, $performance);
    //     return view('sellers.pages.executive-performance', compact(
    //         'seller',
    //         'performance',
    //         'orders',
    //         'months',
    //         'totals',
    //         'leadStatuses',
    //         'clientsWithOrders',
    //         'riskyClients'
    //     ));
    // }

    /**
     * Seller leaderboard (all sellers ranked)
     */
    public function sellerSellerLeaderboard()
    {
        $actor = auth('seller')->user();
        abort_unless($actor, 403, 'Unauthorized.');

        $actor->loadMissing('brand:id,brand_name');

        $sellers = Seller::query()
            ->with(['leads.orders'])
            ->where('brand_id', $actor->brand_id)
            ->where('status', 'Active')
            ->get();

        $ranked = $sellers->map(function (Seller $seller) {
            $totalLeads = $seller->leads->count();
            $orders = $seller->leads->flatMap->orders;
            $paidOrders = $orders->where('status', 'paid');
            $totalOrders = $paidOrders->count();
            $revenueCents = (int) $paidOrders->sum(fn ($o) => (int) ($o->amount_paid ?? 0));
            $revenue = $revenueCents / 100;
            $conversion = $totalLeads > 0 ? round(($totalOrders / $totalLeads) * 100, 1) : 0;

            $responseHours = $seller->leads
                ->filter(fn ($l) => $l->first_response_at)
                ->avg(fn ($l) => $l->created_at->diffInMinutes($l->first_response_at) / 60);

            $clientIds = $seller->leads->pluck('client_id')->filter()->unique();
            $churnScore = $clientIds->isNotEmpty()
                ? (float) (RiskyClient::whereIn('client_id', $clientIds)->avg('risk_score') ?? 0)
                : 0.0;

            $finalScore = round(($conversion * 0.4) + ($revenue * 0.4) - ($churnScore * 0.2), 2);

            return [
                'seller_id'   => $seller->id,
                'name'        => $seller->name,
                'role'        => Str::headline($seller->is_seller ?? 'seller'),
                'conversion'  => $conversion,
                'revenue'     => $revenue,
                'response'    => $responseHours ? round($responseHours, 1) . ' hrs' : '—',
                'churn_score' => round($churnScore, 1),
                'final_score' => $finalScore,
            ];
        })
            ->sortByDesc('final_score')
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });

        return view('sellers.pages.leaderboard', [
            'ranked'    => $ranked,
            'brandName' => $actor->brand?->brand_name,
        ]);
    }

    /**
     * Change seller’s brand/domain
     */
    public function changeDomain(Request $request)
    {
        PortalAuthorization::requireAdmin();

        $request->validate([
            'seller_id' => 'required|exists:sellers,id',
            'brand_id'  => 'required|exists:brands,id',
        ]);

        $seller = Seller::findOrFail($request->seller_id);
        $seller->brand_id = $request->brand_id;
        $seller->save();

        return redirect()->back()->with('success', 'Domain updated successfully.');
    }
}
