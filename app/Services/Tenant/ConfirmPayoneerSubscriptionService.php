<?php

namespace App\Services\Tenant;

use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use RuntimeException;

class ConfirmPayoneerSubscriptionService
{
    public function __construct(
        private readonly ActivateTenantSubscriptionService $activationService,
    ) {}

    public function confirm(TenantPayment $payment, ?string $adminNote = null): TenantPayment
    {
        if ($payment->gateway !== 'payoneer') {
            throw new RuntimeException('This payment is not a Payoneer subscription payment.');
        }

        return $this->activationService->activate(
            $payment,
            renewedBy: 'payoneer',
            note: $adminNote,
            actorType: 'super_admin',
            actorId: auth('super_admin')->id(),
            actorName: auth('super_admin')->user()?->name ?? 'Super Admin',
        );
    }
}
