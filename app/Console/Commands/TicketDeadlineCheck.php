<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientTicket;
use App\Services\ProjectNotify;
use Carbon\Carbon;

class TicketDeadlineCheck extends Command
{
    protected $signature = 'tickets:deadline-check';
    protected $description = 'Send deadline reminders for tickets';

    public function handle()
    {
        $now = Carbon::now();

        // 1) 24 hours left
        $twentyFour = $now->copy()->addHours(24);
        $tickets24 = ClientTicket::where('deadline', $twentyFour)
            ->where('status', '!=', 'closed')
            ->get();

        foreach ($tickets24 as $ticket) {
            ProjectNotify::deadline($ticket, '24_hours');
        }

        // 2) 1 hour left
        $oneHour = $now->copy()->addHour();
        $tickets1 = ClientTicket::where('deadline', $oneHour)
            ->where('status', '!=', 'closed')
            ->get();

        foreach ($tickets1 as $ticket) {
            ProjectNotify::deadline($ticket, '1_hour');
        }

        // 3) Overdue
        $ticketsOverdue = ClientTicket::where('deadline', '<', $now)
            ->where('status', '!=', 'closed')
            ->where('deadline_notified_overdue', false)
            ->get();

        foreach ($ticketsOverdue as $ticket) {
            ProjectNotify::deadline($ticket, 'overdue');
            $ticket->update(['deadline_notified_overdue' => true]);
        }

        $this->info("Deadline notifications checked.");
    }
}
