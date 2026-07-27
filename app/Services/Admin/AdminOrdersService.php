<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Seller;
use App\Support\PortalAuthorization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AdminOrdersService
{
    /** Columns used by admin order tables and row actions. */
    private const LIST_COLUMNS = [
        'id',
        'order_type',
        'parent_order_id',
        'lead_id',
        'brand_id',
        'seller_id',
        'client_id',
        'service_name',
        'currency',
        'unit_amount',
        'amount_paid',
        'balance_due',
        'status',
        'buyer_name',
        'buyer_email',
        'paid_at',
        'created_at',
    ];

    /** @var list<string> */
    public const ORDER_STATUSES = [
        'draft',
        'pending',
        'paid',
        'canceled',
        'in_progress',
        'revision',
        'completed',
    ];

    public function paginatedOriginalOrders(Request $request): LengthAwarePaginator
    {
        $this->assertAdmin();

        $query = $this->baseListQuery(withPaymentLink: true)
            ->where('order_type', 'original');

        $this->applyFilters($query, $request);

        return $query->latest('orders.id')->paginate(20)->withQueryString();
    }

    public function paginatedPmOrders(Request $request): LengthAwarePaginator
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        $query = $this->baseListQuery(withPaymentLink: false);
        $this->applyRoleScope($query, $seller, $isAdmin);
        $this->applyFilters($query, $request);

        return $query->latest('orders.id')->paginate(20)->withQueryString();
    }

    /**
     * @return array{order: Order, renewals: Collection<int, Order>}
     */
    public function renewalsPayload(int $orderId): array
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (! $seller && ! $isAdmin) {
            abort(403, 'You must be logged in.');
        }

        $order = Order::query()
            ->select(self::LIST_COLUMNS)
            ->with($this->eagerLoads(withPaymentLink: true))
            ->findOrFail($orderId);

        if ($order->order_type === 'renewal' && $order->parent_order_id) {
            $order = Order::query()
                ->select(self::LIST_COLUMNS)
                ->with($this->eagerLoads(withPaymentLink: true))
                ->findOrFail($order->parent_order_id);
        }

        PortalAuthorization::authorizeOrder($order);

        $renewals = Order::query()
            ->select(self::LIST_COLUMNS)
            ->with($this->eagerLoads(withPaymentLink: true))
            ->where('parent_order_id', $order->id)
            ->latest('orders.id')
            ->get();

        return compact('order', 'renewals');
    }

    private function assertAdmin(): void
    {
        if (! auth('admin')->check()) {
            abort(403, 'You must be logged in.');
        }
    }

    private function baseListQuery(bool $withPaymentLink): Builder
    {
        return Order::query()
            ->select(array_map(static fn (string $col) => "orders.{$col}", self::LIST_COLUMNS))
            ->with($this->eagerLoads($withPaymentLink));
    }

    /** @return array<string, mixed> */
    private function eagerLoads(bool $withPaymentLink): array
    {
        $loads = [
            'brand:id,brand_name',
            'client:id,name,email',
            'seller:id,name,email,sudo_name,brand_id',
        ];

        if ($withPaymentLink) {
            $loads['latestPaymentLink'] = static fn ($q) => $q->select([
                'id',
                'order_id',
                'is_active_link',
                'last_issued_url',
            ]);
        }

        return $loads;
    }

    private function applyRoleScope(Builder $query, ?Seller $seller, bool $isAdmin): void
    {
        if ($seller) {
            $role = $seller->role ?? $seller->is_seller;

            $query->where('brand_id', $seller->brand_id);

            if ($role !== 'front_seller') {
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
            $query->where('orders.status', $request->string('status'));
        }

        if ($request->filled('brand_id')) {
            $query->where('orders.brand_id', (int) $request->brand_id);
        }

        if ($request->filled('q')) {
            $term = '%' . addcslashes(trim($request->string('q')), '%_\\') . '%';

            $query->where(function (Builder $w) use ($term) {
                $w->where('orders.service_name', 'like', $term)
                    ->orWhere('orders.buyer_name', 'like', $term)
                    ->orWhere('orders.buyer_email', 'like', $term);
            });
        }
    }
}
