<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminPaymentsService
{
    /** Columns used by the admin payments table. */
    private const LIST_COLUMNS = [
        'id',
        'order_id',
        'payment_link_id',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_payment_intent_id',
        'refund_status',
        'refunded_amount',
        'seller_id',
        'credit_to_seller_id',
        'created_at',
    ];

    /** Order columns needed for nested display in the payments table. */
    private const ORDER_COLUMNS = [
        'id',
        'brand_id',
        'seller_id',
        'client_id',
        'service_name',
        'order_type',
        'buyer_name',
        'buyer_email',
    ];

    /** @var list<string> */
    public const PAYMENT_STATUSES = [
        'pending',
        'succeeded',
        'failed',
        'refunded',
        'partially_refunded',
    ];

    /** @var list<string> */
    public const PROVIDERS = [
        'stripe',
        'paypal',
    ];

    public function paginatedList(Request $request): LengthAwarePaginator
    {
        $query = Payment::withoutGlobalScopes()
            ->select(array_map(static fn (string $col) => "payments.{$col}", self::LIST_COLUMNS))
            ->with([
                'paymentLink:id,order_id,provider',
                'order' => function ($q) {
                    $q->select(array_map(static fn (string $col) => "orders.{$col}", self::ORDER_COLUMNS))
                        ->with([
                            'brand:id,brand_name',
                            'client:id,name,email',
                            'seller:id,name,email,sudo_name,brand_id',
                        ]);
                },
            ]);

        $this->applyTenantScope($query);
        $this->applyFilters($query, $request);

        return $query->latest('payments.id')->paginate(20)->withQueryString();
    }

    /**
     * Match payments to the active tenant, including legacy rows with null tenant_id
     * but a parent order that belongs to this tenant.
     */
    private function applyTenantScope(Builder $query): void
    {
        $tenantId = TenantContext::require();

        $query->where(function (Builder $q) use ($tenantId) {
            $q->where('payments.tenant_id', $tenantId)
                ->orWhere(function (Builder $q2) use ($tenantId) {
                    $q2->whereNull('payments.tenant_id')
                        ->whereHas('order', fn (Builder $order) => $order
                            ->where('orders.tenant_id', $tenantId));
                });
        });
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('payments.status', $request->string('status'));
        }

        if ($request->filled('provider')) {
            $query->where('payments.provider', $request->string('provider'));
        }

        if ($request->filled('q')) {
            $term = '%' . addcslashes(trim($request->string('q')), '%_\\') . '%';

            $query->where(function (Builder $w) use ($term) {
                $w->where('payments.provider_payment_intent_id', 'like', $term)
                    ->orWhereHas('order', function (Builder $order) use ($term) {
                        $order->where('orders.service_name', 'like', $term)
                            ->orWhere('orders.buyer_name', 'like', $term)
                            ->orWhere('orders.buyer_email', 'like', $term)
                            ->orWhereHas('client', function (Builder $client) use ($term) {
                                $client->where('name', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    });
            });
        }
    }
}
