<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use Illuminate\Support\Facades\Mail;

class AutoReplyPendingLeads extends Command
{
    protected $signature = 'leads:auto-reply';
    protected $description = 'Auto reply to leads not updated within 24 hours after reaching seller';

    public function handle()
    {
        $this->info('Checking for pending leads...');

        $leads = Lead::where('status', 'new')
            ->where('created_at', '<=', now()->subHours(24))
            ->whereNull('auto_replied')
            ->get();

        foreach ($leads as $lead) {
            Mail::send('emails.lead-follow-up', ['lead' => $lead], function ($message) use ($lead) {
                $message->to($lead->email)
                    ->subject('We’re still here to help you!');
            });
            $this->info("Auto replied to lead ID: {$lead->id}");
            // 🔹 Add small delay (Mailtrap free plan allows ~1 mail/sec)
            sleep(1);
            $lead->update(['auto_replied' => true]);
        }

        $this->info('Auto-reply process complete.');
    }
}
