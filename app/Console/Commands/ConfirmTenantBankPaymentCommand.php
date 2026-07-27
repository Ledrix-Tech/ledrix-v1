<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ConfirmManualSubscriptionService;
use Illuminate\Console\Command;

class ConfirmTenantBankPaymentCommand extends Command
{
    protected $signature = 'tenants:confirm-bank-payment {tenant : Tenant ID, email, or slug}';

    protected $description = 'Confirm the latest pending Meezan/bank transfer payment (for local testing)';

    public function handle(ConfirmManualSubscriptionService $confirmService): int
    {
        $identifier = $this->argument('tenant');

        $tenant = Tenant::query()
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $payment = TenantPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $payment) {
            $this->error('No pending bank transfer payment for this tenant.');

            return self::FAILURE;
        }

        $confirmService->confirm($payment, 'Confirmed via tenants:confirm-bank-payment');

        $this->info("Bank payment confirmed for {$tenant->email}. Subscription activated.");

        return self::SUCCESS;
    }
}
