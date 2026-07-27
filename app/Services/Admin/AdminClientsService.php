<?php

namespace App\Services\Admin;

use App\Models\Client;
use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminClientsService
{
    /** Columns required by the admin clients table and actions. */
    private const LIST_COLUMNS = [
        'id',
        'name',
        'email',
        'phone',
        'status',
        'meta',
        'created_at',
    ];

    public function paginatedList(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Client::query()->select(self::LIST_COLUMNS);

        $seller = auth('seller')->user();

        if ($seller instanceof Seller && ! auth('admin')->check()) {
            $role = $seller->role ?? $seller->is_seller;

            if ($role === 'front_seller') {
                $query->where(function ($q) use ($seller) {
                    $q->whereHas('orders', fn ($o) => $o->where('brand_id', $seller->brand_id))
                        ->orWhereHas('leads', fn ($l) => $l->where('brand_id', $seller->brand_id));
                });
            } else {
                $query->where(function ($q) use ($seller) {
                    $q->whereHas('orders', fn ($o) => $o->where(function ($w) use ($seller) {
                        $w->where('seller_id', $seller->id)
                            ->orWhere('owner_seller_id', $seller->id);
                    }))
                        ->orWhereHas('leads', fn ($l) => $l->where('seller_id', $seller->id));
                });
            }
        }

        return $query
            ->when($search, function ($query, $search) {
                $term = '%' . addcslashes(trim($search), '%_\\') . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
