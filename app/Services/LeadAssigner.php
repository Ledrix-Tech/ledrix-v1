<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Brand;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;

class LeadAssigner
{
    public function assignNext(Brand|int $brand): Seller
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        // Sellers for this brand; add status filter if you have one.
        $sellers = Seller::where('brand_id', $brandId)
            ->where('status', 'Active')
            ->where('is_seller', 'front_seller')
            ->orderBy('id')
            ->get();

        if ($sellers->isEmpty()) {
            throw new \RuntimeException('No sellers available for this brand.');
        }

        // Define what "unfinished lead" means for you:
        // If you have a boolean is_finish column:
        // $unfinishedQuery = fn($q) => $q->where('is_finish', false);
        // If you use statuses, treat these as unfinished:
        $unfinishedStatuses = ['new', 'contacted', 'qualified'];

        // Precompute unfinished counts per seller (brand-scoped) to avoid N+1
        $counts = Lead::select('seller_id', DB::raw('COUNT(*) as c'))
            ->where('brand_id', $brandId)
            ->whereIn('seller_id', $sellers->pluck('id'))
            ->whereIn('status', $unfinishedStatuses) // swap with ->where('is_finish', false) if you use that column
            ->groupBy('seller_id')
            ->pluck('c', 'seller_id'); // [seller_id => count]

        // Prefer a seller with zero unfinished leads
        foreach ($sellers as $seller) {
            if ((int)($counts[$seller->id] ?? 0) === 0) {
                return $seller;
            }
        }

        // Fallback: round-robin among brand sellers based on the last lead’s seller (brand-scoped)
        $lastSellerId = Lead::where('brand_id', $brandId)->latest('id')->value('seller_id'); // correct column
        $ids = $sellers->pluck('id')->values();

        if ($lastSellerId !== null) {
            $idx = $ids->search($lastSellerId);
            $nextIndex = $idx !== false ? ($idx + 1) % $ids->count() : 0;
        } else {
            $nextIndex = 0;
        }

        return $sellers[$nextIndex];
    }
}
