<?php

namespace App\Services\Tenant;

use App\Mail\TenantSubscriptionExpiredMail;
use App\Mail\TenantSubscriptionRenewalReminderMail;
use App\Models\Central\TenantMembership;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcessTenantSubscriptionsService
{
    public function run(): array
    {
        return [
            'reminders_7d'    => $this->sendRenewalReminders(7, 'renewal_reminder_7d_sent_at'),
            'reminders_3d'    => $this->sendRenewalReminders(3, 'renewal_reminder_3d_sent_at'),
            'reminders_1d'    => $this->sendRenewalReminders(1, 'renewal_reminder_1d_sent_at'),
            'marked_past_due' => $this->markExpiredActiveAsPastDue(),
            'expired'         => $this->expireOverdueMemberships(),
        ];
    }

    private function sendRenewalReminders(int $daysBefore, string $sentAtColumn): int
    {
        if (! in_array($daysBefore, config('subscription.renewal_reminder_days', [7, 3, 1]), true)) {
            return 0;
        }

        $count = 0;
        $targetDate = now()->addDays($daysBefore)->toDateString();

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'active')
            ->whereNull($sentAtColumn)
            ->whereNotNull('end_date')
            ->whereDate('end_date', $targetDate)
            ->get();

        foreach ($memberships as $membership) {
            $tenant = $membership->tenant;

            if (! $tenant || $tenant->isSuspended() || $tenant->isCancelled()) {
                continue;
            }

            $daysLeft = $membership->daysUntilExpiry();

            try {
                Mail::to($tenant->email)->send(
                    new TenantSubscriptionRenewalReminderMail($tenant, $membership, $daysLeft)
                );

                $membership->update([$sentAtColumn => now()]);
                $count++;
            } catch (Throwable $e) {
                Log::warning('Subscription renewal reminder email failed', [
                    'tenant_id'     => $tenant->id,
                    'membership_id' => $membership->id,
                    'days_before'   => $daysBefore,
                    'message'       => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function markExpiredActiveAsPastDue(): int
    {
        $count = 0;

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $membership->update(['status' => 'past_due']);
            $count++;

            $tenant = $membership->tenant;

            if (! $tenant || $membership->renewal_expired_notice_sent_at) {
                continue;
            }

            try {
                Mail::to($tenant->email)->send(new TenantSubscriptionExpiredMail($tenant, $membership));
                $membership->update(['renewal_expired_notice_sent_at' => now()]);
            } catch (Throwable $e) {
                Log::warning('Subscription expired notice email failed', [
                    'tenant_id'     => $tenant->id,
                    'membership_id' => $membership->id,
                    'message'       => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function expireOverdueMemberships(): int
    {
        $graceDays = (int) config('subscription.past_due_grace_days', 7);
        $count = 0;

        $memberships = TenantMembership::query()
            ->where('status', 'past_due')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->subDays($graceDays)->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $membership->update(['status' => 'expired']);
            $count++;
        }

        return $count;
    }
}
