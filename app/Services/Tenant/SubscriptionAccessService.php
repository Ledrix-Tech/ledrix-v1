<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;

class SubscriptionAccessService
{
    public function canUseCrm(Tenant $tenant): bool
    {
        if ($tenant->isSuspended() || $tenant->isCancelled()) {
            return false;
        }

        if (! $tenant->isEmailVerified()) {
            return false;
        }

        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        if ($membership->status === 'active' && ! $membership->isExpired()) {
            return true;
        }

        if ($membership->status === 'trialing' && $tenant->isOnTrial()) {
            return true;
        }

        return false;
    }

    public function needsPayment(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        return in_array($membership->status, ['past_due', 'trialing_restricted', 'expired'], true)
            || ($membership->status === 'active' && $membership->isExpired())
            || ($membership->status === 'trialing' && ! $tenant->isOnTrial());
    }

    public function canPayOnBilling(Tenant $tenant): bool
    {
        if ($tenant->isSuspended() || $tenant->isCancelled()) {
            return false;
        }

        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        if ($this->needsPayment($tenant)) {
            return true;
        }

        if ($tenant->isOnTrial()) {
            return true;
        }

        $earlyRenewDays = (int) config('subscription.early_renew_days', 7);

        return $membership->status === 'active'
            && ! $membership->isExpired()
            && $membership->expiresSoon($earlyRenewDays);
    }

    public function paymentOrderType(Tenant $tenant): string
    {
        $membership = $this->currentMembership($tenant);

        if ($membership && in_array($membership->status, ['active', 'past_due', 'expired'], true)) {
            return 'renewal';
        }

        return 'new';
    }

    public function expiresSoon(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        if (! $membership || $membership->status !== 'active' || $membership->isExpired()) {
            return false;
        }

        return $membership->expiresSoon((int) config('subscription.early_renew_days', 7));
    }

    public function isOnTrial(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        return $membership
            && $membership->status === 'trialing'
            && $tenant->isOnTrial();
    }

    public function currentMembership(Tenant $tenant): ?TenantMembership
    {
        return $tenant->memberships()
            ->latest('start_date')
            ->first();
    }
}
