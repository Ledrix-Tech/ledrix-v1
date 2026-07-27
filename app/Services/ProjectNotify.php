<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ClientTicket;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketDeadlineNotification;

class ProjectNotify
{
    /** Notify when ticket is created */
    public static function created(ClientTicket $ticket): void
    {
        $order  = $ticket->order;
        $fs     = $order->frontSeller ?? $order->seller;
        $pm     = $order->ownerSeller;
        $admins = Admin::where('role', 'admin')->get();

        // FS
        if ($fs) {
            $fs->notify(new TicketCreatedNotification($ticket));
        }

        // PM
        if ($pm && $pm->id !== ($fs->id ?? null)) {
            $pm->notify(new TicketCreatedNotification($ticket));
        }

        // Admins
        Notification::send($admins, new TicketCreatedNotification($ticket));
    }


    /** Deadline reminders */
    public static function deadline(ClientTicket $ticket, string $when): void
    {
        $fs = $ticket->order->frontSeller;
        $pm = $ticket->order->ownerSeller;
        $admins = Admin::where('role', 'admin')->get();

        if ($fs) $fs->notify(new TicketDeadlineNotification($ticket, $when));
        if ($pm) $pm->notify(new TicketDeadlineNotification($ticket, $when));

        Notification::send($admins, new TicketDeadlineNotification($ticket, $when));
    }
}
