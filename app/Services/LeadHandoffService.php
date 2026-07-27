<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentLink;
use Illuminate\Support\Facades\DB;

class LeadHandoffService
{
    /**
     * Transfer lead ownership to a PM and re-assign open Orders/active PaymentLinks.
     * Keeps historical (paid/canceled) rows untouched.
     */
    public function assignToSeller(Lead $lead, int $newSellerId): void
    {
        DB::transaction(function () use ($lead, $newSellerId) {
            // 1) Update lead owner
            $lead->update(['seller_id' => $newSellerId]);

            // 2) Reassign OPEN orders to new owner
            Order::where('lead_id', $lead->id)
                ->whereIn('status', ['draft', 'pending'])
                ->update([
                    'seller_id'       => $newSellerId,
                    'owner_seller_id' => $newSellerId,
                ]);

            // 3) Reassign ACTIVE payment links to new owner (don’t touch paid/canceled)
            PaymentLink::where('lead_id', $lead->id)
                ->where('status', 'active')
                ->update([
                    'owner_seller_id' => $newSellerId,
                ]);
        });
    }
}
