<?php



namespace App\Services\Tenant;



use App\Mail\TenantTrialEndingMail;

use App\Models\Central\TenantMembership;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use Throwable;



class ProcessTenantTrialsService

{

    public function run(): array

    {

        $stats = [

            'reminders_sent' => 0,

            'trials_ended'   => 0,

            'expired'        => 0,

        ];



        $stats['reminders_sent'] = $this->sendTrialReminders();

        $stats['trials_ended'] = $this->markTrialsEnded();

        $stats['expired'] = $this->expireOverdueMemberships();



        return $stats;

    }



    private function sendTrialReminders(): int

    {

        $reminderDays = (int) config('services.jazzcash.trial_reminder_days', 3);

        $count = 0;



        $memberships = TenantMembership::query()

            ->with(['tenant.plan'])

            ->where('status', 'trialing')

            ->whereNull('trial_reminder_sent_at')

            ->whereDate('trial_end', '<=', now()->addDays($reminderDays)->toDateString())

            ->whereDate('trial_end', '>=', now()->toDateString())

            ->get();



        foreach ($memberships as $membership) {

            $tenant = $membership->tenant;



            if (! $tenant || ! $tenant->isOnTrial()) {

                continue;

            }



            try {

                Mail::to($tenant->email)->send(new TenantTrialEndingMail($tenant, $membership));

                $membership->update(['trial_reminder_sent_at' => now()]);

                $count++;

            } catch (Throwable $e) {

                Log::warning('Trial reminder email failed', [

                    'tenant_id' => $tenant->id,

                    'message'   => $e->getMessage(),

                ]);

            }

        }



        return $count;

    }



    private function markTrialsEnded(): int

    {

        $count = 0;



        $memberships = TenantMembership::query()

            ->with(['tenant'])

            ->where('status', 'trialing')

            ->whereDate('trial_end', '<', now()->toDateString())

            ->get();



        foreach ($memberships as $membership) {

            $membership->update(['status' => 'past_due']);

            $count++;

        }



        return $count;

    }



    private function expireOverdueMemberships(): int

    {

        $graceDays = (int) config('services.jazzcash.grace_days', 7);

        $count = 0;



        $memberships = TenantMembership::query()

            ->where('status', 'past_due')

            ->whereDate('end_date', '<', now()->subDays($graceDays)->toDateString())

            ->get();



        foreach ($memberships as $membership) {

            $membership->update(['status' => 'expired']);

            $count++;

        }



        return $count;

    }

}


