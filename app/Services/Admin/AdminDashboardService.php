<?php

namespace App\Services\Admin;

use App\Models\Brand;
use App\Models\Central\SystemAnnouncement;
use App\Models\Central\Tenant;
use App\Models\Lead;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantUsageService;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminDashboardService
{
    public function __construct(
        private SubscriptionAccessService $subscriptionAccess,
        private TenantUsageService $usageService,
    ) {}

    /**
     * Build all data required by the admin dashboard view.
     *
     * @return array<string, mixed>
     */
    public function build(?int $brandId = null): array
    {
        $tenantId = TenantContext::require();

        $brands = Brand::query()
            ->select('id', 'brand_name')
            ->orderBy('brand_name')
            ->get();

        if ($brandId && ! $brands->contains('id', $brandId)) {
            abort(404);
        }

        $stats = $this->kpiCounts($tenantId, $brandId);
        $ppcPayments = $this->paymentLinkTotals('payment_links', $tenantId, $brandId);
        $upworkPayments = $this->paymentLinkTotals('upwork_payment_links', $tenantId, $brandId);
        [$months, $totals] = $this->revenueChart($tenantId, $brandId);

        $recentLeads = Lead::query()
            ->with('brand:id,brand_name')
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'service', 'domain_url', 'brand_id', 'created_at']);

        $merchants = Brand::query()
            ->select('id', 'brand_name')
            ->when($brandId, fn ($q) => $q->where('id', $brandId))
            ->withCount('orders')
            ->withSum(['paymentLinks as total_revenue' => fn ($q) => $q->where('status', 'paid')], 'unit_amount')
            ->withSum(['paymentLinks as chargebacks' => fn ($q) => $q->where('status', 'chargeback')], 'unit_amount')
            ->orderByDesc('total_revenue')
            ->get();

        $logs = $this->leadViewLogs($brands, $brandId);

        return array_merge([
            'leads'             => (int) ($stats->leads ?? 0),
            'orders'            => (int) ($stats->orders ?? 0),
            'payments'          => (int) ($stats->payments ?? 0),
            'brands'            => $brands,
            'months'            => $months,
            'totals'            => $totals,
            'revenue'           => $ppcPayments['paid'],
            'paymentPaid'       => $ppcPayments['paid'],
            'paymentDue'        => $ppcPayments['due'],
            'upworkPaymentPaid' => $upworkPayments['paid'],
            'upworkPaymentDue'  => $upworkPayments['due'],
            'upworkRevenue'     => $upworkPayments['paid'],
            'upworkSnapshot'    => $upworkPayments['snapshot'],
            'recentLeads'       => $recentLeads,
            'merchants'         => $merchants,
            'logs'              => $logs,
            'selectedBrandId'   => $brandId,
        ], $this->saasContext($tenantId));
    }

    /**
     * Subscription health, announcements, and plan usage for Admin CRM (A-02–A-04).
     *
     * @return array<string, mixed>
     */
    private function saasContext(int $tenantId): array
    {
        $empty = [
            'saasTenant'           => null,
            'saasMembership'       => null,
            'saasPlan'             => null,
            'saasUsage'            => null,
            'saasLimits'           => [],
            'saasNeedsPayment'     => false,
            'saasExpiresSoon'      => false,
            'saasDaysUntilRenewal' => 0,
            'saasOnTrial'          => false,
            'saasTrialDaysLeft'    => 0,
            'saasAnnouncements'    => collect(),
        ];

        try {
            $tenant = Tenant::query()->with(['plan', 'activeMembership'])->find($tenantId);

            if (! $tenant) {
                return $empty;
            }

            $usage = $this->usageService->syncSnapshot($tenantId);
            $tenant->setRelation('usageSnapshot', $usage);

            $membership = $this->subscriptionAccess->currentMembership($tenant);
            $plan = $tenant->plan;

            $announcements = SystemAnnouncement::query()
                ->visible()
                ->forPlan((string) ($plan?->slug ?? ''))
                ->latest()
                ->get()
                ->filter(fn (SystemAnnouncement $a) => $a->isVisibleToTenant($tenant))
                ->values();

            return [
                'saasTenant'           => $tenant,
                'saasMembership'       => $membership,
                'saasPlan'             => $plan,
                'saasUsage'            => $usage,
                'saasLimits'           => [
                    'brands'        => $plan?->max_brands,
                    'sellers'       => $plan?->max_sellers,
                    'admins'        => $plan?->max_admins,
                    'clients'       => $plan?->max_clients,
                    'leads_monthly' => $plan?->max_leads_per_month,
                    'orders'        => $plan?->max_orders,
                ],
                'saasNeedsPayment'     => $this->subscriptionAccess->needsPayment($tenant),
                'saasExpiresSoon'      => $this->subscriptionAccess->expiresSoon($tenant),
                'saasDaysUntilRenewal' => $membership?->daysUntilExpiry() ?? 0,
                'saasOnTrial'          => $tenant->isOnTrial(),
                'saasTrialDaysLeft'    => $tenant->isOnTrial() ? $tenant->trialDaysLeft() : 0,
                'saasAnnouncements'    => $announcements,
            ];
        } catch (\Throwable $e) {
            Log::debug('Admin dashboard SaaS context unavailable', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * @return object{leads: int, orders: int, payments: int}
     */
    private function kpiCounts(?int $tenantId, ?int $brandId): object
    {
        $leadWhere = 'deleted_at IS NULL';
        $orderWhere = 'deleted_at IS NULL';
        $paymentWhere = 'p.deleted_at IS NULL AND o.deleted_at IS NULL';
        $bindings = [];

        if ($tenantId) {
            $leadWhere .= ' AND tenant_id = ?';
            $orderWhere .= ' AND tenant_id = ?';
            $paymentWhere .= ' AND p.tenant_id = ? AND o.tenant_id = ?';
        } else {
            abort(403, 'Tenant workspace could not be resolved for dashboard metrics.');
        }

        if ($brandId) {
            $leadWhere .= ' AND brand_id = ?';
            $orderWhere .= ' AND brand_id = ?';
            $paymentWhere .= ' AND o.brand_id = ?';
        }

        if ($tenantId) {
            $bindings[] = $tenantId;
        }
        if ($brandId) {
            $bindings[] = $brandId;
        }
        if ($tenantId) {
            $bindings[] = $tenantId;
        }
        if ($brandId) {
            $bindings[] = $brandId;
        }
        if ($tenantId) {
            $bindings[] = $tenantId;
            $bindings[] = $tenantId;
        }
        if ($brandId) {
            $bindings[] = $brandId;
        }

        return DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM leads WHERE {$leadWhere}) AS leads,
                (SELECT COUNT(*) FROM orders WHERE {$orderWhere}) AS orders,
                (SELECT COUNT(*)
                    FROM payments p
                    INNER JOIN orders o ON o.id = p.order_id
                    WHERE {$paymentWhere}) AS payments
        ", $bindings);
    }

    /**
     * Paid / due totals from payment link tables (grouped per order for snapshot dedup).
     *
     * @return array{paid: int, due: int, snapshot: int}
     */
    private function paymentLinkTotals(string $table, ?int $tenantId, ?int $brandId): array
    {
        $query = DB::table($table)
            ->whereNull('deleted_at');

        if ($tenantId && \Illuminate\Support\Facades\Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $row = DB::query()->fromSub(
            $query
                ->select('order_id')
                ->selectRaw('MAX(order_total_snapshot) as snapshot')
                ->selectRaw("SUM(CASE WHEN status = 'paid' THEN unit_amount ELSE 0 END) as paid")
                ->groupBy('order_id'),
            'order_totals'
        )
            ->selectRaw('COALESCE(SUM(paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(snapshot), 0) as snapshot')
            ->first();

        $paid = (int) ($row->paid ?? 0);
        $snapshot = (int) ($row->snapshot ?? 0);

        return [
            'paid'     => $paid,
            'snapshot' => $snapshot,
            'due'      => max(0, $snapshot - $paid),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<float|int>}
     */
    private function revenueChart(?int $tenantId, ?int $brandId): array
    {
        $query = DB::table('orders')
            ->selectRaw('MONTH(created_at) as month, SUM(unit_amount) / 100 as total_income')
            ->where('status', 'paid')
            ->whereNull('deleted_at');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $rows = $query
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $totals = [];

        foreach ($rows as $row) {
            $months[] = date('F', mktime(0, 0, 0, (int) $row->month, 1));
            $totals[] = $row->total_income;
        }

        return [$months, $totals];
    }

    /**
     * @param  Collection<int, Brand>  $brands
     * @return list<string>
     */
    private function leadViewLogs(Collection $brands, ?int $brandId): array
    {
        $targets = $brandId
            ? $brands->where('id', $brandId)
            : $brands;

        if ($targets->isEmpty()) {
            return ['No brand lead view logs found.'];
        }

        $allLines = [];

        foreach ($targets as $brand) {
            $path = $this->brandLogPath($brand);

            if (! File::exists($path)) {
                continue;
            }

            try {
                $lines = self::tailLogLines($path, $brandId ? 20 : 10);
                foreach ($lines as $line) {
                    $allLines[] = '[' . $brand->brand_name . '] ' . $line;
                }
            } catch (\Throwable $e) {
                $allLines[] = "⚠️ Failed to read {$brand->brand_name} log: " . $e->getMessage();
            }
        }

        if ($allLines === []) {
            return ['No brand lead view logs found.'];
        }

        return collect($allLines)
            ->sortDesc()
            ->take(50)
            ->values()
            ->all();
    }

    private function brandLogPath(Brand $brand): string
    {
        $slug = Str::slug($brand->brand_name, '_');

        return storage_path("logs/brands/{$slug}/lead-views.log");
    }

    /** Read the last N non-empty lines without loading the whole file into memory. */
    public static function tailLogLines(string $path, int $lines = 20): array
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = (int) $file->key();
        $start = max(0, $lastLine - max($lines * 3, $lines));

        $buffer = [];

        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $line = trim((string) $file->current());
            if ($line !== '') {
                $buffer[] = $line;
            }
        }

        return array_slice(array_reverse($buffer), 0, $lines);
    }
}
