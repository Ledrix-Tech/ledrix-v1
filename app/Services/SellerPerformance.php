<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Lead;
use App\Models\Seller;
use App\Models\Payment;
use App\Models\LeadAssignment;
use App\Models\PerformanceBonus;
use App\Models\RiskyClient;
use Illuminate\Support\Facades\DB;

class SellerPerformance
{
    /**
     * Build full seller performance analytics.
     */
    public static function build(int $sellerId): array
    {
        $seller = Seller::with('brand')->findOrFail($sellerId);

        // -----------------------------------------------
        // 1) LOAD ALL PAYMENTS CREDITED TO THIS SELLER
        // -----------------------------------------------
        $creditedPayments = Payment::with(['order.lead.client', 'paymentLink'])
            ->where('credit_to_seller_id', $seller->id)
            ->latest('created_at')
            ->get();

        // -----------------------------------------------
        // 2) REVENUE + REFUND + CHARGEBACK CALCULATIONS
        // -----------------------------------------------
        $grossCents      = (int) $creditedPayments->sum('amount'); // before refunds
        $refundCents     = (int) $creditedPayments->sum('refunded_amount');
        $chargebackCents = (int) $creditedPayments
            ->where('refund_status', 'chargeback')
            ->sum('amount');

        $netCents        = $grossCents - ($refundCents + $chargebackCents);

        $revenue     = $grossCents / 100;
        $refunds     = $refundCents / 100;
        $chargebacks = $chargebackCents / 100;
        $netRevenue  = $netCents / 100;

        // -----------------------------------------------
        // 3) MONTHLY NET REVENUE (gross - refunds - chargebacks)
        // -----------------------------------------------
        $monthlyIncome = $creditedPayments
            ->groupBy(fn($p) => $p->created_at->format('Y-m'))
            ->map(function ($group) {
                $gross      = $group->sum('amount');
                $refund     = $group->sum('refunded_amount');
                $chargeback = $group->where('refund_status', 'chargeback')->sum('amount');
                return ($gross - ($refund + $chargeback)) / 100;
            });

        $months = $monthlyIncome->keys()->values()->all();
        $totals = $monthlyIncome->values()->all();

        // Growth calculations
        $currentMonth   = now()->format('Y-m');
        $previousMonth  = now()->subMonth()->format('Y-m');

        $currentRevenue  = $monthlyIncome[$currentMonth]  ?? 0;
        $previousRevenue = $monthlyIncome[$previousMonth] ?? 0;

        if ($previousRevenue > 0) {
            $growth = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
        } elseif ($currentRevenue > 0) {
            $growth = 100;
        } else {
            $growth = 0;
        }

        // -----------------------------------------------
        // 4) PIPELINE (unpaid payment links)
        // -----------------------------------------------
        $pipelineAmount = (float) DB::table('payment_links')
            ->when(
                $seller->is_seller === 'front_seller',
                fn($q) => $q->where('credit_to_seller_id', $seller->id)
            )
            ->when(
                $seller->is_seller === 'project_manager',
                fn($q) => $q->where('owner_seller_id', $seller->id)
            )
            ->where('status', '!=', 'paid')
            ->sum('unit_amount') / 100;

        // -----------------------------------------------
        // 5) FORECAST: last 3 months weighted
        // -----------------------------------------------
        $values = collect($monthlyIncome)->sortKeysDesc()->take(3)->values();
        $weights = collect([3, 2, 1])->take($values->count());

        $forecastNextMonth = $values->count() > 0
            ? round($values->zip($weights)->sum(fn($pair) => $pair[0] * $pair[1]) / $weights->sum(), 2)
            : 0;

        // -----------------------------------------------
        // 6) BONUS RULES
        // -----------------------------------------------
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd   = now()->endOfMonth();

        $bonusRule = PerformanceBonus::where('seller_id', $seller->id)
            ->where(function ($q) use ($currentMonthStart, $currentMonthEnd) {
                $q->whereNull('period_start')
                    ->orWhere(function ($q2) use ($currentMonthStart, $currentMonthEnd) {
                        $q2->where('period_start', '<=', $currentMonthEnd)
                            ->where(function ($q3) use ($currentMonthStart) {
                                $q3->whereNull('period_end')
                                    ->orWhere('period_end', '>=', $currentMonthStart);
                            });
                    });
            })
            ->first();

        $bonusEarned = 0;
        $bonusProgress = 0;

        if ($bonusRule) {
            $target = (float) $bonusRule->target_revenue;
            $bonusProgress = $target > 0 ? round(min(($revenue / $target) * 100, 100), 2) : 0;
            if ($revenue >= $target) {
                $bonusEarned = $bonusRule->bonus_amount;
            }
        }

        // -----------------------------------------------
        // 7) LOAD ORDERS TIED TO CREDITED PAYMENTS
        // -----------------------------------------------
        $orderIds = $creditedPayments->pluck('order_id')->unique()->values();
        $orders = Order::with(['payments' => function ($q) use ($seller) {
            $q->where('credit_to_seller_id', $seller->id);
        }, 'lead.client'])
            ->whereIn('id', $orderIds)
            ->get();

        $totalOrders   = $orders->count();
        $paidOrders    = $orders->where('status', 'paid')->count();
        $unpaidOrders  = $totalOrders - $paidOrders;
        $totalDue      = (int) $orders->sum('balance_due') / 100;

        // -----------------------------------------------
        // 8) LEAD STATS
        // -----------------------------------------------
        if ($seller->is_seller === 'front_seller') {
            $leadBaseQuery = Lead::where('seller_id', $seller->id);
        } else {
            $assignedLeadIds = LeadAssignment::where('assigned_to', $seller->id)->pluck('lead_id');
            $leadBaseQuery = Lead::whereIn('id', $assignedLeadIds);
        }

        $totalLeads = (clone $leadBaseQuery)->count();

        $leadStatuses = (clone $leadBaseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $convertedLeadIds = $orders->pluck('lead_id')->unique()->filter();
        $convertedLeads = $convertedLeadIds->count();

        // -----------------------------------------------
        // 9) CLIENTS + RECENT ORDERS
        // -----------------------------------------------
        $clientsWithOrders = $orders
            ->groupBy(fn($o) => $o->lead?->client?->id)
            ->map(function ($group) {
                $client = optional($group->first()->lead)->client;
                return [
                    'client'       => $client,
                    'orders'       => $group->values(),
                    'last_payment' => $group->flatMap->payments->sortByDesc('created_at')->first(),
                ];
            })
            ->values();

        // -----------------------------------------------
        // 10) RISKY CLIENTS FOR THIS SELLER
        // -----------------------------------------------
        $riskyClients = RiskyClient::with([
            'client.orders' => fn($q) => $q->latest()->take(10),
            'client.orders.payments',
        ])
            ->whereHas('client.leads', function ($q) use ($seller) {
                if ($seller->is_seller === 'front_seller') {
                    $q->where('seller_id', $seller->id);
                } else {
                    $assignedLeadIds = LeadAssignment::where('assigned_to', $seller->id)
                        ->pluck('lead_id');
                    $q->whereIn('id', $assignedLeadIds);
                }
            })
            ->orderByDesc('risk_score')
            ->get();


        // forcast revenue for sellers (based on orders)
        $sellerMonthly = Payment::selectRaw("
        orders.seller_id,
        DATE_FORMAT(payments.created_at, '%Y-%m') AS month,
        SUM(payments.amount - payments.refunded_amount) AS net
    ")
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->groupBy('orders.seller_id', 'month')
            ->get()
            ->groupBy('orders.seller_id'); // Group by seller_id to get data for each seller

        $sellerForecasts = [];
        foreach ($sellerMonthly as $sellerId => $rows) {
            $values = collect($rows)
                ->sortByDesc('month')  // Sort the data by month (latest first)
                ->take(3)              // Take the last 3 months (or adjust as needed)
                ->pluck('net')         // Get the net revenue for the last 3 months
                ->values();

            // Apply weights (e.g., 3, 2, 1 for the last 3 months)
            $weights = collect([3, 2, 1])->take($values->count());

            // Calculate the weighted average forecast
            $forecast = $values->count() > 0
                ? round($values->zip($weights)->sum(fn($pair) => $pair[0] * $pair[1]) / $weights->sum(), 2)
                : 0;

            $sellerForecasts[$sellerId] = (int) $forecast; // Store the forecast in cents
        }

        // -----------------------------------------------
        // ✅ RETURN EXACT FORMAT REQUIRED BY BLADE
        // -----------------------------------------------
        $performance = [
            'total_leads'     => $totalLeads,
            'total_orders'    => $totalOrders,
            'paid_orders'     => $paidOrders,
            'unpaid_orders'   => $unpaidOrders,

            'gross_revenue'   => $revenue,
            'refunds'         => $refunds,
            'chargebacks'     => $chargebacks,
            'net_revenue'     => $netRevenue,

            'total_due'       => $totalDue,
            'conversion_rate' => $totalLeads > 0
                ? round(($convertedLeads / $totalLeads) * 100, 2)
                : 0,

            'avg_order_value' =>
            $totalOrders > 0 ? round($netRevenue / max(1, $totalOrders), 2) : 0,

            'monthly_growth'  => $growth,
            'pipeline_amount' => $pipelineAmount,
            'forecast_next_month' => $forecastNextMonth,

            'bonus_rule_target' => $bonusRule?->target_revenue,
            'bonus_rule_amount' => $bonusRule?->bonus_amount,
            'bonus_earned'      => $bonusEarned,
            'bonus_progress'    => $bonusProgress,

            'pipeline_ratio' =>
            $netRevenue > 0 ? round(($pipelineAmount / $netRevenue) * 100, 2) : 0,
        ];

        return [
            'sellerForecasts'  => $sellerForecasts,  // Seller forecasts

            'seller'            => $seller,
            'performance'       => $performance,
            'orders'            => $orders,
            'months'            => $months,
            'totals'            => $totals,
            'leadStatuses'      => $leadStatuses,
            'clientsWithOrders' => $clientsWithOrders,
            'riskyClients'      => $riskyClients,
        ];

    }
}
