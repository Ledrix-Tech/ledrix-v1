<?php

namespace App\Services\Billing;

use App\Models\Central\TenantPayment;
use RuntimeException;

class ConfirmManualSubscriptionService
{
    public function __construct(
        private readonly ActivateTenantSubscriptionService $activationService,
    ) {}

    public function confirm(TenantPayment $payment, ?string $adminNote = null): TenantPayment
    {
        if (! in_array($payment->gateway, ['payoneer', 'bank_transfer'], true)) {
            throw new RuntimeException('This payment cannot be manually confirmed.');
        }

        $renewedBy = $payment->gateway === 'payoneer' ? 'payoneer' : 'tenant';

        $isCli = app()->runningInConsole();
        $superAdmin = auth('super_admin')->user();

        return $this->activationService->activate(
            $payment,
            renewedBy: $renewedBy,
            note: $adminNote,
            actorType: $superAdmin ? 'super_admin' : ($isCli ? 'system' : 'tenant'),
            actorId: $superAdmin?->id,
            actorName: $superAdmin?->name ?? ($isCli ? 'CLI' : 'Tenant'),
        );
    }
}
