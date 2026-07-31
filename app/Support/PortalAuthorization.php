<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ClientTicket;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\Order;
use App\Models\Seller;

class PortalAuthorization
{
    private static function adminTenantId(): ?int
    {
        $admin = auth('admin')->user();

        return ($admin instanceof Admin && $admin->tenant_id)
            ? (int) $admin->tenant_id
            : null;
    }

    private static function resourceBelongsToAdminTenant(?int $resourceTenantId): bool
    {
        $adminTenantId = self::adminTenantId();

        if ($adminTenantId === null) {
            return true;
        }

        return $resourceTenantId !== null && $adminTenantId === (int) $resourceTenantId;
    }

    public static function actor(): Admin|Seller|null
    {
        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            return $admin;
        }

        $seller = auth('seller')->user();

        return $seller instanceof Seller ? $seller : null;
    }

    public static function requirePortalActor(): Admin|Seller
    {
        $actor = self::actor();
        abort_unless($actor, 403, 'Unauthorized.');

        if ($actor instanceof Admin && ($actor->role ?? null) === 'finance') {
            abort(403, 'Finance accounts cannot perform this action.');
        }

        return $actor;
    }

    public static function requireAdmin(): Admin
    {
        abort_unless(auth('admin')->check(), 403, 'Administrator access required.');

        $admin = auth('admin')->user();
        abort_if(($admin->role ?? null) === 'finance', 403, 'Finance accounts cannot perform this action.');

        return $admin;
    }

    public static function canViewLead(Lead $lead): bool
    {
        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            return ($admin->role ?? 'admin') !== 'finance'
                && self::resourceBelongsToAdminTenant($lead->tenant_id);
        }

        $seller = auth('seller')->user();

        if (! $seller instanceof Seller) {
            return false;
        }

        $role = $seller->role ?? $seller->is_seller;

        if ($role === 'front_seller') {
            return (int) $lead->brand_id === (int) $seller->brand_id;
        }

        if ($role === 'project_manager') {
            return LeadAssignment::query()
                ->where('lead_id', $lead->id)
                ->where('assigned_to', $seller->id)
                ->exists();
        }

        return (int) $lead->seller_id === (int) $seller->id;
    }

    public static function authorizeLead(Lead $lead): void
    {
        abort_unless(self::canViewLead($lead), 403, 'You cannot access this lead.');
    }

    public static function canManageOrder(Order $order): bool
    {
        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            return ($admin->role ?? 'admin') !== 'finance'
                && self::resourceBelongsToAdminTenant($order->tenant_id);
        }

        $seller = auth('seller')->user();

        if (! $seller instanceof Seller) {
            return false;
        }

        $role = $seller->role ?? $seller->is_seller;

        if ($role === 'front_seller') {
            return (int) $order->brand_id === (int) $seller->brand_id;
        }

        if ($order->lead_id && LeadAssignment::query()
            ->where('lead_id', $order->lead_id)
            ->where('assigned_to', $seller->id)
            ->exists()) {
            return true;
        }

        return (int) $order->seller_id === (int) $seller->id
            || (int) $order->owner_seller_id === (int) $seller->id;
    }

    public static function authorizeOrder(Order $order): void
    {
        abort_unless(self::canManageOrder($order), 403, 'You cannot access this order.');
    }

    public static function canManageClient(Client $client): bool
    {
        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            return ($admin->role ?? 'admin') !== 'finance'
                && self::resourceBelongsToAdminTenant($client->tenant_id);
        }

        $seller = auth('seller')->user();

        if (! $seller instanceof Seller) {
            return false;
        }

        $role = $seller->role ?? $seller->is_seller;

        if ($role === 'front_seller') {
            return $client->orders()->where('brand_id', $seller->brand_id)->exists()
                || $client->leads()->where('brand_id', $seller->brand_id)->exists();
        }

        return $client->orders()->where(function ($q) use ($seller) {
            $q->where('seller_id', $seller->id)
                ->orWhere('owner_seller_id', $seller->id);
        })->exists()
            || $client->leads()->where('seller_id', $seller->id)->exists();
    }

    public static function authorizeClient(Client $client): void
    {
        abort_unless(self::canManageClient($client), 403, 'You cannot access this client.');
    }

    public static function canManageTicket(ClientTicket $ticket): bool
    {
        $order = $ticket->order;

        if ($order instanceof Order) {
            return self::canManageOrder($order);
        }

        return auth('admin')->check() && (auth('admin')->user()->role ?? null) !== 'finance';
    }

    public static function authorizeTicket(ClientTicket $ticket): void
    {
        abort_unless(self::canManageTicket($ticket), 403, 'You cannot access this ticket.');
    }

    public static function requireFrontSellerOrAdmin(): Admin|Seller
    {
        $actor = self::requirePortalActor();

        if ($actor instanceof Admin) {
            return $actor;
        }

        $role = $actor->role ?? $actor->is_seller;
        abort_unless($role === 'front_seller', 403, 'Only front sellers can perform this action.');

        return $actor;
    }

    public static function authorizeSameBrandSeller(Seller $target): void
    {
        if (auth('admin')->check()) {
            return;
        }

        $actor = auth('seller')->user();
        abort_unless(
            $actor instanceof Seller && (int) $actor->brand_id === (int) $target->brand_id,
            403,
            'You cannot manage sellers outside your brand.'
        );
    }

    public static function authorizeBrand(Brand $brand): void
    {
        if (auth('admin')->check()) {
            abort_unless(
                self::resourceBelongsToAdminTenant($brand->tenant_id),
                403,
                'You cannot access this brand.'
            );

            return;
        }

        $seller = auth('seller')->user();
        abort_unless(
            $seller instanceof Seller && (int) $seller->brand_id === (int) $brand->id,
            403,
            'You cannot access this brand.'
        );
    }
}
