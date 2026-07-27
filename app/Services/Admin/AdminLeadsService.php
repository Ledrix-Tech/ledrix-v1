<?php

namespace App\Services\Admin;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Seller;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminLeadsService
{
    public function __construct(
        private TenantFeatureService $tenantFeatures,
    ) {}

    /** Columns used by the admin leads table and row actions. */
    private const LIST_COLUMNS = [
        'id',
        'name',
        'email',
        'phone',
        'domain_url',
        'meta',
        'status',
        'brand_id',
        'seller_id',
        'created_at',
    ];

    /** Columns used by the admin lead detail page. */
    private const DETAIL_COLUMNS = [
        'id',
        'name',
        'email',
        'phone',
        'message',
        'service',
        'status',
        'prediction',
        'domain_url',
        'meta',
        'brand_id',
        'seller_id',
        'client_id',
        'created_at',
    ];

    /**
     * @return array{
     *     leads: LengthAwarePaginator,
     *     pmSellers: Collection<int, Seller>,
     *     showLeadPrediction: bool
     * }
     */
    public function paginatedList(Request $request): array
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();
        $showLeadPrediction = $this->tenantFeatures->hasLeadPrediction();

        $query = $this->baseListQuery($showLeadPrediction);
        $this->applyRoleScope($query, $seller, $isAdmin);
        $this->applyFilters($query, $request);

        return [
            'leads'              => $query->latest('leads.id')->paginate(20)->withQueryString(),
            'pmSellers'          => $this->projectManagers(),
            'showLeadPrediction' => $showLeadPrediction,
        ];
    }

    /**
     * @return array{lead: Lead, showLeadPrediction: bool}
     */
    public function detailPayload(int $id): array
    {
        $showLeadPrediction = $this->tenantFeatures->hasLeadPrediction();
        $columns = self::DETAIL_COLUMNS;

        if (! $showLeadPrediction) {
            $columns = array_values(array_diff($columns, ['prediction']));
        }

        $lead = Lead::query()
            ->select($columns)
            ->with([
                'brand:id,brand_name,brand_url',
                'seller:id,name,email,brand_id,is_seller',
                'client:id,name,email,phone',
            ])
            ->findOrFail($id);

        return [
            'lead'               => $lead,
            'showLeadPrediction' => $showLeadPrediction,
        ];
    }

    public function showLeadPrediction(): bool
    {
        return $this->tenantFeatures->hasLeadPrediction();
    }

    public function findForDetail(int $id): Lead
    {
        return $this->detailPayload($id)['lead'];
    }

    public function paginatedAssignedList(Request $request): LengthAwarePaginator
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        $showLeadPrediction = $this->tenantFeatures->hasLeadPrediction();

        $query = Lead::query()
            ->select($this->listColumns($showLeadPrediction))
            ->with([
                'brand:id,brand_name',
                'seller:id,name,email,brand_id,is_seller',
                'assignments' => function ($q) use ($seller, $isAdmin) {
                    if ($seller && ! $isAdmin) {
                        $q->where('assigned_to', $seller->id);
                    }
                    $q->latest('assigned_at')
                        ->limit(1)
                        ->with(['assignee:id,name,email']);
                },
            ]);

        $this->addLatestOrderSubselects($query);

        if ($seller) {
            $query->whereHas('assignments', fn ($q) => $q->where('assigned_to', $seller->id));
        } elseif (! $isAdmin) {
            abort(403, 'You must be logged in.');
        }

        $this->applyFilters($query, $request);

        return $query->latest('leads.id')->paginate(20)->withQueryString();
    }

    /**
     * Active project managers for assign dropdowns (tenant-scoped via BelongsToTenant).
     *
     * @return Collection<int, Seller>
     */
    public function projectManagers(): Collection
    {
        return Seller::query()
            ->select('id', 'name', 'brand_id')
            ->where('is_seller', 'project_manager')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();
    }

    private function baseListQuery(bool $withPrediction = false): Builder
    {
        $query = Lead::query()
            ->select($this->listColumns($withPrediction))
            ->with(array_merge([
                'brand:id,brand_name',
                'seller:id,name,email,brand_id,is_seller',
            ], $this->latestAssignmentEagerLoad()));

        $this->addLatestOrderSubselects($query);

        return $query;
    }

    /** @return list<string> */
    private function listColumns(bool $withPrediction = false): array
    {
        $cols = self::LIST_COLUMNS;

        if ($withPrediction) {
            $cols = array_merge($cols, ['prediction']);
        }

        return array_map(static fn (string $col) => "leads.{$col}", $cols);
    }

    private function latestAssignmentEagerLoad(): array
    {
        return [
            'latestAssignment' => static function ($query) {
                $query->select([
                    'lead_assignments.id',
                    'lead_assignments.lead_id',
                    'lead_assignments.status',
                    'lead_assignments.assigned_to',
                    'lead_assignments.assigned_role',
                    'lead_assignments.assigned_by',
                    'lead_assignments.assigned_at',
                ]);
            },
        ];
    }

    private function addLatestOrderSubselects(Builder $query): void
    {
        $query->addSelect([
            'latest_order_id' => Order::select('id')
                ->whereColumn('orders.lead_id', 'leads.id')
                ->whereNull('orders.deleted_at')
                ->orderByDesc('orders.id')
                ->limit(1),
            'latest_order_balance_due' => Order::select('balance_due')
                ->whereColumn('orders.lead_id', 'leads.id')
                ->whereNull('orders.deleted_at')
                ->orderByDesc('orders.id')
                ->limit(1),
            'latest_order_currency' => Order::select('currency')
                ->whereColumn('orders.lead_id', 'leads.id')
                ->whereNull('orders.deleted_at')
                ->orderByDesc('orders.id')
                ->limit(1),
        ]);
    }

    private function applyRoleScope(Builder $query, ?Seller $seller, bool $isAdmin): void
    {
        if ($seller) {
            $role = $seller->role ?? $seller->is_seller;
            if ($role === 'front_seller') {
                $query->where('brand_id', $seller->brand_id);
            } else {
                $query->where('seller_id', $seller->id);
            }

            return;
        }

        if (! $isAdmin) {
            abort(403, 'You must be logged in.');
        }
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->brand_id);
        }

        if ($request->filled('q')) {
            $term = '%' . addcslashes(trim($request->q), '%_\\') . '%';

            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('message', 'like', $term);
            });
        }
    }
}
