<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lead;
use App\Models\Brand;
use App\Models\Seller;
use Illuminate\Http\Request;
use App\Models\LeadAssignment;
use Illuminate\Validation\Rule;
use App\Services\SellerPerformance;
use App\Services\Tenant\TenantLimitService;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LeadAssignedPmNotification;

class AdminSellerController extends Controller
{
    public function adminSellers()
    {
        $sellers = Seller::paginate(20);
        $brands = Brand::where('module', 'ppc')->get();
        // dd($sellers);
        return view('admin.pages.executives', compact('sellers', 'brands'));
    }

    public function adminSellerPost(Request $request)
    {
        $validated = $request->validate([
            'brand_id'    => 'required|exists:brands,id',
            'sudo_name'   => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'is_seller' => ['required', Rule::in(['project_manager', 'front_seller'])],
            'email'       => 'required|email|unique:sellers,email',
            'password'    => 'required|min:8',
        ]);

        $brand = Brand::findOrFail($validated['brand_id']);
        if ($brand->tenant_id) {
            app(TenantLimitService::class)->assertCanCreateSeller((int) $brand->tenant_id);
        }

        $fileName = null;
        // if ($request->hasFile('pro_image')) {
        //     $file = $request->pro_image;
        //     $fileName = time() . '_' . $file->getClientOriginalName();
        //     $file->move(public_path('uploads/products/'), $fileName);
        // }

        $seller = new Seller();
        $seller->brand_id    = $validated['brand_id'];
        $seller->sudo_name   = $validated['sudo_name'];
        $seller->is_seller   = $validated['is_seller'];
        $seller->name = $validated['name'];
        $seller->email       = $validated['email'];
        $seller->password    = $validated['password'];

        // dd($seller, $request->all());
        $seller->save();

        return back()->with('success', 'Seller added successfully.');
    }

    public function sellerUpdateStatus(Request $request)
    {
        $seller = Seller::find($request->user_id); // or Seller model if separate

        if (!$seller) {
            return response()->json(['success' => false]);
        }

        $seller->status = $request->status; // active / inactive
        $seller->save();

        return response()->json(['success' => true]);
    }

    public function adminSellerPerformance(int $id)
    {
        // Sellers can only see their own performance
        if (auth('seller')->check() && auth('seller')->id() !== $id) {
            abort(403, 'Unauthorized.');
        }

        $data = SellerPerformance::build($id);

        return view('admin.pages.executive-performance', $data);
    }

    // old performance code seller
    // public function adminSellerPerformance(int $id)
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
    //     return view('admin.pages.executive-performance', compact(
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

    public function assignLeadSeller(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        $validated = $request->validate([
            'lead_id'   => 'required|exists:leads,id',
            'seller_id' => 'required|exists:sellers,id',
        ]);

        $lead  = Lead::findOrFail($validated['lead_id']);
        $pm    = Seller::findOrFail($validated['seller_id']);

        PortalAuthorization::authorizeLead($lead);

        // Ensure brand match
        if ($lead->brand_id !== $pm->brand_id) {
            return back()->with('error', 'Seller must belong to the same brand as this lead.');
        }

        if (auth('seller')->check()) {
            PortalAuthorization::authorizeSameBrandSeller($pm);
        }

        // Determine assigner (Admin OR FS)
        $admin       = auth('admin')->user();
        $frontSeller = auth('seller')->user();

        if ($admin) {
            $assignerId   = $admin->id;
            $assignerRole = 'admin';
            $assignerSellerModel = $admin; // For notification
        } elseif ($frontSeller && $frontSeller->is_seller === 'front_seller') {
            $assignerId   = $frontSeller->id;
            $assignerRole = 'front_seller';
            $assignerSellerModel = $frontSeller; // For notification
        } else {
            return back()->with('error', 'Unauthorized assignment attempt.');
        }

        // Check previous assignment
        $assignment = LeadAssignment::where('lead_id', $lead->id)->first();

        if ($assignment) {
            // Update existing assignment
            $assignment->update([
                'assigned_to'   => $pm->id,
                'assigned_role' => $assignerRole,
                'assigned_by'   => $assignerId,
                'assigned_at'   => now(),
                'status'        => 'assigned',
            ]);
        } else {
            // Create new assignment
            LeadAssignment::create([
                'lead_id'       => $lead->id,
                'assigned_to'   => $pm->id,
                'assigned_role' => $assignerRole,
                'assigned_by'   => $assignerId,
                'assigned_at'   => now(),
                'status'        => 'assigned',
            ]);
        }

        /**
         * 🔥 Notify PM ONLY if the assigned seller is a project manager
         */
        if ($pm->is_seller === 'project_manager' && $pm->email) {
            Notification::route('mail', $pm->email)
                ->notify(
                    (new LeadAssignedPmNotification($lead, $pm, $assignerSellerModel))
                        ->delay(now()->addSeconds(5))
                );
        }

        return redirect()
            ->back()
            ->with('success', 'Lead successfully assigned to seller.');
    }


    // public function assignLeadSeller(Request $request)
    // {
    //     // Validate request
    //     $validated = $request->validate([
    //         'lead_id'   => 'required|exists:leads,id',
    //         'seller_id' => 'required|exists:sellers,id',
    //     ]);

    //     // Ensure seller and lead exists
    //     $lead = Lead::find($validated['lead_id']);
    //     $seller = Seller::find($validated['seller_id']);
    //     if (!$seller) {
    //         return back()->with('error', 'Seller not found!');
    //     }
    //     // Ensure seller is from the same brand as lead
    //     if ($lead->brand_id !== $seller->brand_id) {
    //         return back()->with('error', 'Seller must belong to the same brand as this lead.');
    //     }
    //     // Check logged in user (admin or seller)
    //     $admin       = auth()->guard('admin')->user();
    //     $sellerFront = auth()->guard('seller')->user();

    //     $assignedBy   = null;
    //     $assignedRole = null;

    //     if ($admin) {
    //         $assignedBy   = $admin->id;
    //         $assignedRole = 'admin';
    //     } elseif ($sellerFront && $sellerFront->is_seller === 'front_seller') {
    //         $assignedBy   = $sellerFront->id;
    //         $assignedRole = 'front_seller';
    //     } else {
    //         return back()->with('error', 'Unauthorized assignment attempt.');
    //     }

    //     // Check if lead already has assignment
    //     $assignment = LeadAssignment::where('lead_id', $validated['lead_id'])->first();

    //     if ($assignment) {
    //         // update existing record
    //         $assignment->assigned_to   = $validated['seller_id'];
    //         $assignment->assigned_role = $assignedRole;
    //         $assignment->assigned_by   = $assignedBy;
    //         $assignment->assigned_at   = now();
    //         $assignment->status        = 'assigned';
    //         $assignment->save();
    //     } else {
    //         // create new assignment
    //         LeadAssignment::create([
    //             'lead_id'       => $validated['lead_id'],
    //             'assigned_to'   => $validated['seller_id'],
    //             'assigned_role' => $assignedRole,
    //             'assigned_by'   => $assignedBy,
    //             'assigned_at'   => now(),
    //             'status'        => 'assigned',
    //         ]);
    //     }

    //     return redirect()
    //         ->back()
    //         ->with('success', 'Lead successfully assigned to seller.');
    // }
}
