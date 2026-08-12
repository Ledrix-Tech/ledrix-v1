<?php

namespace App\Services;

use App\Models\{Brand, Lead, Client, LeadAssignment, Order, PaymentLink};
use App\Services\Tenant\TenantLimitService;
use App\Services\Tenant\TenantFeatureService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentLinkService
{
    public function __construct(
        private TenantLimitService $limits,
        private TenantFeatureService $features,
        private PaymentGatewayFactory $gateways,
    ) {}
    public function createInstallmentLink(
        Brand   $brand,
        Lead    $lead,
        int     $sellerIdWhoGenerated,
        string  $serviceName,
        string  $currency,
        int     $totalCents,
        int     $payNowCents,
        ?int    $expiresInHours = null,
        ?string $description = null,
        ?string $provider = null,
        ?string $orderType = 'original',      // 'original' | 'renewal'
        ?int    $baseOrderId = null,          // ✅ can be ORIGINAL id OR RENEWAL id
        array   $meta = []
    ): PaymentLink {

        // ✅ Service-level defensive validation (controller is not a security boundary)
        $orderType = $orderType ?: 'original';
        abort_unless(in_array($orderType, ['original', 'renewal'], true), 422, 'Invalid order type.');

        abort_unless($provider && in_array($provider, ['stripe', 'paypal'], true), 422, 'Invalid provider.');

        abort_unless(
            $this->gateways->brandHasProvider($brand, $provider, 'ppc'),
            422,
            'No active '.$provider.' merchant is configured for this brand. Add Payment Accounts before generating a link.'
        );

        $tenantId = (int) ($brand->tenant_id ?? TenantContext::resolve() ?? 0);

        if ($tenantId) {
            TenantContext::set($tenantId);
            $this->features->assertProviderEnabled($provider, $tenantId);
            $this->limits->assertCanCreatePaymentLink($tenantId);
        }

        if ($payNowCents < $totalCents) {
            $this->features->assertEnabled('milestone_payments', $tenantId ?: null);
        }

        $currency = strtoupper(trim($currency));
        abort_unless(strlen($currency) === 3, 422, 'Invalid currency.');

        $serviceName = trim($serviceName);
        abort_unless($serviceName !== '', 422, 'Service name is required.');

        abort_unless($totalCents > 0, 422, 'Total must be > 0.');
        abort_unless($payNowCents > 0, 422, 'Payable must be > 0.');
        abort_unless($payNowCents <= $totalCents, 422, 'Payable cannot exceed total.');

        return DB::transaction(function () use (
            $brand,
            $lead,
            $sellerIdWhoGenerated,
            $serviceName,
            $currency,
            $totalCents,
            $payNowCents,
            $expiresInHours,
            $description,
            $provider,
            $orderType,
            $baseOrderId,
            $meta,
            $tenantId,
        ) {

            $client = $this->resolveClientForLead($lead, $tenantId);

            [$frontSellerId, $currentOwnerId] = $this->resolveAttributionSellers($lead);

            // Create/reuse order based on type
            $order = ($orderType === 'renewal')
                ? $this->getOrReuseRenewalOrder(
                    brand: $brand,
                    lead: $lead,
                    client: $client,
                    baseOrderId: $baseOrderId,
                    serviceName: $serviceName,
                    currency: $currency,
                    totalCents: $totalCents,
                    frontSellerId: $frontSellerId,
                    currentOwnerId: $currentOwnerId,
                )
                : $this->getOrCreateOriginalOrder(
                    brand: $brand,
                    lead: $lead,
                    client: $client,
                    serviceName: $serviceName,
                    currency: $currency,
                    totalCents: $totalCents,
                    frontSellerId: $frontSellerId,
                    currentOwnerId: $currentOwnerId,
                );

            $this->syncOrderOwner($order, $currentOwnerId);

            // Re-lock order and block if an active unused link already exists (milestones: one active link at a time).
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $this->assertNoActiveUnusedLink($order->id);

            // ✅ The only amount that matters is what’s due on THAT order
            abort_unless($payNowCents <= (int)$order->balance_due, 422, 'Payable exceeds remaining balance.');

            $creditSellerId = $this->creditSellerIdFor($order, $frontSellerId, $currentOwnerId);

            $generatedById   = auth('admin')->id() ?? auth('seller')->id();
            $generatedByType = auth('admin')->check() ? 'admin' : 'seller';

            // Create payment link
            return PaymentLink::create([
                'tenant_id'            => $tenantId ?: null,
                'lead_id'              => $lead->id,
                'brand_id'             => $brand->id,
                'client_id'            => $client->id,
                'order_id'             => $order->id,
                'service_name'         => $serviceName,
                'description'          => $description,
                'provider'             => $provider,
                'currency'             => $currency,
                'unit_amount'          => $payNowCents,
                'order_total_snapshot' => (int)$order->unit_amount,
                'token'                => Str::random(48),
                'status'               => 'active',
                'is_active_link'       => true,
                'expires_at'           => now()->addHours(max(1, min(720, (int)($expiresInHours ?? 168)))),

                'seller_id'            => $currentOwnerId,
                'owner_seller_id'      => $currentOwnerId,
                'credit_to_seller_id'  => $creditSellerId,

                'generated_by_id'      => $generatedById,
                'generated_by_type'    => $generatedByType,

                // 'meta' => $meta,
            ]);
        });
    }

    // ----------------------------
    // Helpers
    // ----------------------------

    /**
     * Prevent multiple concurrent checkout links on the same order.
     * Milestone links are allowed sequentially once the prior link is paid, expired, or deactivated.
     */
    private function assertNoActiveUnusedLink(int $orderId): void
    {
        $hasActive = PaymentLink::withoutGlobalScopes()
            ->where('order_id', $orderId)
            ->where('is_active_link', true)
            ->where('status', '!=', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        abort_if(
            $hasActive,
            422,
            'This order already has an active payment link. Deactivate it or wait for it to expire before creating a new milestone link.'
        );
    }

    private function resolveClientForLead(Lead $lead, int $tenantId = 0): Client
    {
        if ($lead->client_id) {
            $client = Client::withoutGlobalScopes()->find($lead->client_id);
        } else {
            $email = $lead->email ? strtolower(trim($lead->email)) : null;

            $client = $email
                ? Client::withoutGlobalScopes()->whereRaw('LOWER(email)=?', [$email])->first()
                : null;

            if (!$client) {
                if ($tenantId) {
                    $this->limits->assertCanCreateClient($tenantId);
                }

                $client = Client::create([
                    'tenant_id' => $tenantId ?: $lead->tenant_id,
                    'name'      => $lead->name ?: 'Unknown',
                    'email'     => $email,
                    'phone'     => $lead->phone,
                ]);
            }

            $lead->client()->associate($client);
            $lead->save();
        }

        if (!$client) {
            abort(422, 'Client resolution failed.');
        }

        if (!$client->name || !$client->phone) {
            $client->fill([
                'name'  => $client->name ?: ($lead->name ?: $client->name),
                'phone' => $client->phone ?: $lead->phone,
            ])->save();
        }

        return $client;
    }

    private function resolveAttributionSellers(Lead $lead): array
    {
        $assignment = LeadAssignment::where('lead_id', $lead->id)->latest('assigned_at')->first();

        // Prefer the lead's owning front seller — never treat an Admin id as a seller id.
        $frontSellerId = (int) ($lead->seller_id ?: $lead->getOriginal('seller_id'));
        if (
            $assignment
            && ($assignment->assigned_role ?? null) === 'front_seller'
            && (int) $assignment->assigned_by > 0
        ) {
            $frontSellerId = (int) $assignment->assigned_by;
        }

        $currentOwnerId = (int) ($assignment?->assigned_to ?: $lead->seller_id);

        abort_unless($frontSellerId > 0, 422, 'Front seller not resolved.');
        abort_unless($currentOwnerId > 0, 422, 'Owner seller not resolved.');

        return [$frontSellerId, $currentOwnerId];
    }

    private function getOrCreateOriginalOrder(
        Brand $brand,
        Lead $lead,
        Client $client,
        string $serviceName,
        string $currency,
        int $totalCents,
        int $frontSellerId,
        int $currentOwnerId,
    ): Order {

        $order = Order::query()
            ->where('lead_id', $lead->id)
            ->where('brand_id', $brand->id)
            ->where('client_id', $client->id)
            ->where('service_name', $serviceName)
            ->where('order_type', 'original')
            ->whereIn('status', ['pending']) // expand if you use 'partial'
            ->lockForUpdate()
            ->first();

        if (!$order) {
            $tenantId = (int) ($brand->tenant_id ?? 0);

            if ($tenantId) {
                $this->limits->assertCanCreateOrder($tenantId);
            }

            return Order::create([
                'tenant_id'       => $brand->tenant_id,
                'lead_id'         => $lead->id,
                'brand_id'        => $brand->id,
                'seller_id'       => $currentOwnerId,
                'front_seller_id' => $frontSellerId,
                'owner_seller_id' => $currentOwnerId,
                'client_id'       => $client->id,
                'service_name'    => $serviceName,
                'currency'        => $currency,
                'unit_amount'     => $totalCents,
                'status'          => 'pending',
                'buyer_name'      => $lead->name,
                'buyer_email'     => $lead->email,
                'order_type'      => 'original',
                'parent_order_id' => null,
            ]);
        }

        abort_unless($order->currency === $currency, 422, 'Currency mismatch.');

        // allow only increase
        if ($totalCents > (int)$order->unit_amount) {
            $order->update(['unit_amount' => $totalCents]);
        }

        return $order;
    }

    /**
     * ✅ baseOrderId can be:
     * - ORIGINAL id  -> create/reuse renewal
     * - RENEWAL id   -> reuse same renewal (half-paid renewal)
     */
    private function getOrReuseRenewalOrder(
        Brand $brand,
        Lead $lead,
        Client $client,
        ?int $baseOrderId,
        string $serviceName,
        string $currency,
        int $totalCents,
        int $frontSellerId,
        int $currentOwnerId,
    ): Order {

        abort_unless($baseOrderId, 422, 'Order id is required for renewal.');

        $base = Order::lockForUpdate()->findOrFail($baseOrderId);

        // CASE A: base is renewal => reuse it, but original must be paid first
        if ($base->order_type === 'renewal') {
            $renewal = $base;

            abort_unless((int)$renewal->brand_id === (int)$brand->id, 422, 'Renewal brand mismatch.');
            abort_unless((int)$renewal->lead_id === (int)$lead->id, 422, 'Renewal lead mismatch.');
            abort_unless((int)$renewal->client_id === (int)$client->id, 422, 'Renewal client mismatch.');
            abort_unless(strcasecmp($renewal->service_name, $serviceName) === 0, 422, 'Renewal service mismatch.');
            abort_unless($renewal->currency === $currency, 422, 'Currency mismatch.');

            // Original parent must be fully paid before ANY renewal payments
            $original = Order::lockForUpdate()->findOrFail($renewal->parent_order_id);

            abort_unless(
                (int)$original->balance_due === 0,
                422,
                'Original order must be fully paid before collecting renewal payments.'
            );

            // allow only increase
            if ($totalCents > (int)$renewal->unit_amount) {
                $renewal->update(['unit_amount' => $totalCents]);
            }

            return $renewal;
        }

        // CASE B: base is original => create/reuse renewal
        abort_unless($base->order_type === 'original', 422, 'Invalid base order type for renewal.');

        $original = $base;

        abort_unless((int)$original->brand_id === (int)$brand->id, 422, 'Original brand mismatch.');
        abort_unless((int)$original->lead_id === (int)$lead->id, 422, 'Original lead mismatch.');
        abort_unless((int)$original->client_id === (int)$client->id, 422, 'Original client mismatch.');
        abort_unless(strcasecmp($original->service_name, $serviceName) === 0, 422, 'Original service mismatch.');
        abort_unless($original->currency === $currency, 422, 'Currency mismatch.');

        // ✅ Rule: cannot create renewal until original fully paid
        abort_unless(
            (int)$original->balance_due === 0,
            422,
            'Original order must be fully paid before creating a renewal.'
        );

        // Reuse open renewal for this original (partially paid or awaiting first milestone).
        $renewal = Order::query()
            ->where('order_type', 'renewal')
            ->where('parent_order_id', $original->id)
            ->where('brand_id', $brand->id)
            ->where('client_id', $client->id)
            ->where('lead_id', $lead->id)
            ->where('service_name', $serviceName)
            ->where('balance_due', '>', 0)
            ->lockForUpdate()
            ->first();

        if (!$renewal) {
            if ($brand->tenant_id) {
                $this->limits->assertCanCreateOrder((int) $brand->tenant_id);
            }

            return Order::create([
                'tenant_id'       => $brand->tenant_id,
                'lead_id'         => $lead->id,
                'brand_id'        => $brand->id,
                'seller_id'       => $currentOwnerId,
                'front_seller_id' => $frontSellerId,
                'owner_seller_id' => $currentOwnerId,
                'client_id'       => $client->id,
                'service_name'    => $serviceName,
                'currency'        => $currency,
                'unit_amount'     => $totalCents,
                'status'          => 'pending',
                'buyer_name'      => $lead->name,
                'buyer_email'     => $lead->email,
                'order_type'      => 'renewal',
                'parent_order_id' => $original->id,
            ]);
        }

        abort_unless($renewal->currency === $currency, 422, 'Currency mismatch.');

        if ($totalCents > (int)$renewal->unit_amount) {
            $renewal->update(['unit_amount' => $totalCents]);
        }

        return $renewal;
    }

    private function syncOrderOwner(Order $order, int $currentOwnerId): void
    {
        if ((int)$order->owner_seller_id !== $currentOwnerId) {
            $order->update([
                'owner_seller_id' => $currentOwnerId,
                'seller_id'       => $currentOwnerId,
            ]);
        }
    }

    private function creditSellerIdFor(Order $order, int $frontSellerId, int $currentOwnerId): int
    {
        if ($order->order_type === 'renewal') {
            return $currentOwnerId;
        }

        $isFirstPayment = ((int)$order->amount_paid === 0);
        return $isFirstPayment ? $frontSellerId : $currentOwnerId;
    }
}
// class PaymentLinkService
// {
//     // // Payment link generate
//     // public function createInstallmentLink(
//     //     Brand   $brand,
//     //     Lead    $lead,
//     //     int     $sellerIdWhoGenerated,
//     //     string  $serviceName,
//     //     string  $currency,
//     //     int     $totalCents,
//     //     int     $payNowCents,
//     //     ?int    $expiresInHours = null,
//     //     ?string $description = null,
//     //     ?string $provider = null,
//     //     ?string $orderType = 'original',      // 'original' | 'renewal'
//     //     ?int    $parentOrderId = null,
//     //     array   $meta = []
//     // ): PaymentLink {
//     //     return DB::transaction(function () use (
//     //         $brand,
//     //         $lead,
//     //         $sellerIdWhoGenerated,
//     //         $serviceName,
//     //         $currency,
//     //         $totalCents,
//     //         $payNowCents,
//     //         $expiresInHours,
//     //         $description,
//     //         $provider,
//     //         $orderType,
//     //         $parentOrderId,
//     //         $meta
//     //     ) {
//     //         // 1) Resolve client (no duplicates)
//     //         if ($lead->client_id) {
//     //             $client = $lead->client;
//     //         } else {
//     //             $email  = $lead->email ? strtolower(trim($lead->email)) : null;
//     //             $client = $email
//     //                 ? Client::whereRaw('LOWER(email)=?', [$email])->first()
//     //                 : null;
//     //             if (!$client) {
//     //                 $client = Client::create([
//     //                     'name'  => $lead->name ?: 'Unknown',
//     //                     'email' => $email,
//     //                     'phone' => $lead->phone,
//     //                 ]);
//     //             }
//     //             $lead->client()->associate($client);
//     //             $lead->save();
//     //         }
//     //         if ($client && (!$client->name || !$client->phone)) {
//     //             $client->fill([
//     //                 'name'  => $client->name  ?: ($lead->name ?: $client->name),
//     //                 'phone' => $client->phone ?: $lead->phone,
//     //             ])->save();
//     //         }

//     //         // 2) Determine current owner vs front seller
//     //         $assignment     = LeadAssignment::where('lead_id', $lead->id)->latest('assigned_at')->first();
//     //         $frontSellerId  = (int) ($assignment?->assigned_by ?: $lead->getOriginal('seller_id'));
//     //         $currentOwnerId = (int) ($assignment?->assigned_to ?: $lead->seller_id);

//     //         // 3) ORIGINAL vs RENEWAL creation rules
//     //         $order = null;

//     //         if ($orderType === 'renewal') {
//     //             // 3a) Enforce: parent must be fully paid (and ideally completed)
//     //             abort_unless($parentOrderId, 422, 'Parent order required for renewal.');
//     //             $parent = Order::lockForUpdate()->findOrFail($parentOrderId);

//     //             abort_unless(
//     //                 (int)$parent->lead_id === (int)$lead->id &&
//     //                     (int)$parent->client_id === (int)$client->id &&
//     //                     strcasecmp($parent->service_name, $serviceName) === 0,
//     //                 422,
//     //                 'Parent order mismatch for this renewal.'
//     //             );

//     //             // abort_unless(
//     //             //     (int)$parent->balance_due === 0 && in_array($parent->status, ['paid', 'completed', 'delivered']),
//     //             //     422,
//     //             //     'Parent order must be fully paid before creating a renewal.'
//     //             // );

//     //             // 3b) Reuse an open renewal (if any) to prevent duplicates
//     //             $order = Order::query()
//     //                 ->where('order_type', 'renewal')
//     //                 ->where('parent_order_id', $parent->parent_order_id)
//     //                 ->whereIn('status', ['pending'])
//     //                 ->lockForUpdate()
//     //                 ->first();
//     //             // dd($parent,$order);

//     //             if (!$order) {
//     //                 $order = Order::create([
//     //                     'lead_id'         => $lead->id,
//     //                     'brand_id'        => $brand->id,
//     //                     'seller_id'       => $currentOwnerId,   // current owner for operational responsibility
//     //                     'front_seller_id' => $frontSellerId,    // snapshot for attribution
//     //                     'owner_seller_id' => $currentOwnerId,   // snapshot for attribution
//     //                     'client_id'       => $client->id,
//     //                     'service_name'    => $serviceName,
//     //                     'currency'        => strtoupper($currency),
//     //                     'unit_amount'     => $totalCents,
//     //                     'status'          => 'pending',
//     //                     'buyer_name'      => $lead->name,
//     //                     'buyer_email'     => $lead->email,
//     //                     'order_type'      => 'renewal',
//     //                     'parent_order_id' => $parent->id,
//     //                 ]);
//     //             } else {
//     //                 // allow only increases; currency must match
//     //                 abort_unless($order->currency === strtoupper($currency), 422, 'Currency mismatch.');
//     //                 if ($totalCents > (int)$order->unit_amount) {
//     //                     $order->unit_amount = $totalCents;
//     //                     $order->save();
//     //                 }
//     //             }
//     //         } else {
//     //             // ORIGINAL order path
//     //             // 3c) HARD RULE: You cannot create a NEW original order if any unpaid order exists for same lead+service
//     //             $unpaidExists = Order::query()
//     //                 ->where('lead_id', $lead->id)
//     //                 ->where('brand_id', $brand->id)
//     //                 ->where('client_id', $client->id)
//     //                 ->where('service_name', $serviceName)
//     //                 ->whereIn('status', ['pending'])
//     //                 ->exists();

//     //             // abort_if($unpaidExists, 422, 'You already have an unpaid order for this service.');

//     //             // Reuse an open original if somehow exists (safety), else create
//     //             $order = Order::query()
//     //                 ->where('lead_id', $lead->id)
//     //                 ->where('brand_id', $brand->id)
//     //                 ->where('client_id', $client->id)
//     //                 ->where('service_name', $serviceName)
//     //                 ->where('order_type', 'original')
//     //                 ->whereIn('status', ['pending'])
//     //                 ->lockForUpdate()
//     //                 ->first();

//     //             if (!$order) {
//     //                 $order = Order::create([
//     //                     'lead_id'         => $lead->id,
//     //                     'brand_id'        => $brand->id,
//     //                     'seller_id'       => $currentOwnerId,
//     //                     'front_seller_id' => $frontSellerId,
//     //                     'owner_seller_id' => $currentOwnerId,
//     //                     'client_id'       => $client->id,
//     //                     'service_name'    => $serviceName,
//     //                     'currency'        => strtoupper($currency),
//     //                     'unit_amount'     => $totalCents,
//     //                     'status'          => 'pending',
//     //                     'buyer_name'      => $lead->name,
//     //                     'buyer_email'     => $lead->email,
//     //                     'order_type'      => 'original',
//     //                     'parent_order_id' => null,
//     //                 ]);
//     //             } else {
//     //                 abort_unless($order->currency === strtoupper($currency), 422, 'Currency mismatch.');
//     //                 if ($totalCents > (int)$order->unit_amount) {
//     //                     $order->unit_amount = $totalCents;
//     //                     $order->save();
//     //                 }
//     //             }
//     //         }

//     //         // If owner changed after assignment, refresh on open order
//     //         if ((int)$order->owner_seller_id !== $currentOwnerId) {
//     //             $order->owner_seller_id = $currentOwnerId;
//     //             $order->seller_id       = $currentOwnerId;
//     //             $order->save();
//     //         }

//     //         // 4) Guard amounts and compute credit
//     //         abort_unless($payNowCents <= (int)$order->balance_due, 422, 'Payable exceeds remaining balance.');
//     //         // replaced with belower code
//     //         // $isFirstPayment  = ((int)$order->amount_paid === 0);
//     //         // $creditSellerId  = $isFirstPayment ? (int)$frontSellerId : (int)$currentOwnerId;

//     //         if ($order->order_type === 'renewal') {
//     //             // Always credit PM for renewal payments
//     //             $creditSellerId = (int)$currentOwnerId;
//     //         } else {
//     //             // For original orders:
//     //             //  - First payment → FS
//     //             //  - Future payments → PM
//     //             $isFirstPayment  = ((int)$order->amount_paid === 0);
//     //             $creditSellerId  = $isFirstPayment ? (int)$frontSellerId : (int)$currentOwnerId;
//     //         }

//     //         $generatedById   = auth('admin')->id() ?? auth('seller')->id();
//     //         $generatedByType = auth('admin')->check() ? 'admin' : 'seller';

//     //         // 5) Create Payment Link
//     //         return PaymentLink::create([
//     //             'lead_id'              => $lead->id,
//     //             'brand_id'             => $brand->id,
//     //             'client_id'            => $client->id,
//     //             'order_id'             => $order->id,
//     //             'service_name'         => $serviceName,
//     //             'description'          => $description,
//     //             'provider'             => $provider,
//     //             'currency'             => strtoupper($currency),
//     //             'unit_amount'          => $payNowCents,
//     //             'order_total_snapshot' => (int)$order->unit_amount,
//     //             'token'                => Str::random(48),
//     //             'status'               => 'active',
//     //             'expires_at'           => now()->addHours(max(1, min(720, (int)($expiresInHours ?? 168)))),

//     //             // ownership + attribution
//     //             'seller_id'            => $currentOwnerId,   // current owner at issue time
//     //             'owner_seller_id'      => $currentOwnerId,   // snapshot
//     //             'credit_to_seller_id'  => $creditSellerId,   // front for first, owner for later
//     //             'generated_by_id'      => $generatedById,
//     //             'generated_by_type'    => $generatedByType,

//     //             // keep metadata if you store JSON (optional)
//     //             // 'meta' => $meta,
//     //         ]);
//     //     });
//     // }
// }
