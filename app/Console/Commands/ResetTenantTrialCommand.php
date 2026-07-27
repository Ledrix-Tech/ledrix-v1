<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTenantTrialCommand extends Command
{
    protected $signature = 'tenants:reset-trial
                            {tenant? : Tenant ID, email, or slug}
                            {--days=14 : Trial length in days}
                            {--list : List tenants without resetting}';

    protected $description = 'Reset a tenant to trialing status (for billing tests)';

    public function handle(): int
    {
        if ($this->option('list')) {
            Tenant::query()
                ->select('id', 'name', 'email', 'slug', 'status')
                ->orderBy('id')
                ->get()
                ->each(fn ($t) => $this->line("{$t->id}\t{$t->email}\t{$t->slug}\t{$t->status}"));

            return self::SUCCESS;
        }

        $identifier = $this->argument('tenant');

        if (! $identifier) {
            $this->error('Provide a tenant ID, email, or slug. Use --list to see tenants.');

            return self::FAILURE;
        }

        $tenant = Tenant::query()
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (! $tenant) {
            $this->error("Tenant not found: {$identifier}");

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $trialEnds = now()->addDays($days);

        DB::connection('central')->transaction(function () use ($tenant, $days, $trialEnds) {
            TenantPayment::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'past_due', 'expired', 'trialing_restricted'])
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                    'cancel_reason'=> 'Reset for JazzCash billing test',
                ]);

            $plan = $tenant->plan;
            $billingCycle = $tenant->meta['billing_cycle'] ?? 'monthly';

            TenantMembership::create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $tenant->plan_id,
                'billing_cycle' => $billingCycle,
                'amount'        => $plan?->monthly_price ?? 0,
                'currency'      => $plan?->currency ?? 'USD',
                'api_key'       => $this->uniqueApiKey(),
                'start_date'    => now()->toDateString(),
                'end_date'      => $trialEnds->toDateString(),
                'trial_start'   => now()->toDateString(),
                'trial_end'     => $trialEnds->toDateString(),
                'status'        => 'trialing',
                'renewed_by'    => 'tenant',
                'conversion_source' => 'trial_reset',
                'meta'          => ['trial_days' => $days],
            ]);

            $tenant->update([
                'status'                   => 'active',
                'trial_ends_at'            => $trialEnds,
                'trial_used'               => true,
                'auto_renew'               => false,
                'jazzcash_payment_token'   => null,
                'jazzcash_token_expires_at'=> null,
                'suspended_reason'         => null,
                'suspended_at'             => null,
            ]);
        });

        $this->info("Tenant #{$tenant->id} ({$tenant->email}) reset to {$days}-day trial.");
        $this->line("Trial ends: {$trialEnds->format('M d, Y')}");
        $this->line('Billing: ' . route('tenant.billing'));

        return self::SUCCESS;
    }

    private function uniqueApiKey(): string
    {
        do {
            $key = \Illuminate\Support\Str::random(64);
        } while (TenantMembership::where('api_key', $key)->exists());

        return $key;
    }
}
