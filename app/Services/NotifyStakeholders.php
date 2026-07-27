<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentRefundedNotification;
use App\Notifications\PaymentDisputeNotification;

class NotifyStakeholders
{
    /** REFUND NOTIFICATIONS */
    public static function refund(Payment $payment, Order $order, string $provider, ?string $reason = null): void
    {
        $client  = $order->client;
        $fs      = $order->frontSeller ?? $order->seller;
        $pm      = $order->ownerSeller ?? null;

        $admins  = Admin::where('role', 'admin')->get();
        $finance = Admin::where('role', 'finance')->get();

        $delay = 0;

        // CLIENT (immediate)
        if ($client?->email) {
            Notification::route('mail', $client->email)
                ->notify((new PaymentRefundedNotification($payment, $order, $provider, $reason))
                    ->delay(now()->addSeconds($delay)));
        }

        // FS (+5 sec)
        $delay += 5;
        if ($fs?->email) {
            $fs->notify(
                (new PaymentRefundedNotification($payment, $order, $provider, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // PM (+10 sec, avoid duplicate)
        $delay += 5;
        if ($pm?->email && (!$fs || $pm->id !== $fs->id)) {
            $pm->notify(
                (new PaymentRefundedNotification($payment, $order, $provider, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // ADMINS (+15 sec)
        $delay += 5;
        if ($admins->count()) {
            Notification::send(
                $admins,
                (new PaymentRefundedNotification($payment, $order, $provider, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // FINANCE (+20 sec)
        $delay += 5;
        if ($finance->count()) {
            Notification::send(
                $finance,
                (new PaymentRefundedNotification($payment, $order, $provider, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }
    }

    // public static function refund(Payment $payment, Order $order, string $provider, ?string $reason = null): void
    // {
    //     $client  = $order->client;
    //     $fs      = $order->frontSeller ?? $order->seller;
    //     $pm      = $order->ownerSeller ?? null;

    //     // All admins by role
    //     $admins  = Admin::where('role', 'admin')->get();
    //     $finance = Admin::where('role', 'finance')->get();   // get() not first()

    //     // CLIENT
    //     if ($client?->email) {
    //         Notification::route('mail', $client->email)
    //             ->notify(new PaymentRefundedNotification($payment, $order, $provider, $reason));
    //     }

    //     // FRONT SELLER
    //     if ($fs?->email) {
    //         $fs->notify(new PaymentRefundedNotification($payment, $order, $provider, $reason));
    //     }

    //     // PROJECT MANAGER (avoid sending twice if PM == FS)
    //     if ($pm?->email && (!$fs || $pm->id !== $fs->id)) {
    //         $pm->notify(new PaymentRefundedNotification($payment, $order, $provider, $reason));
    //     }

    //     // ADMINS (all)
    //     if ($admins->count() > 0) {
    //         Notification::send($admins, new PaymentRefundedNotification($payment, $order, $provider, $reason));
    //     }

    //     // FINANCE (all finance users)
    //     if ($finance->count() > 0) {
    //         Notification::send($finance, new PaymentRefundedNotification($payment, $order, $provider, $reason));
    //     }
    // }

    /** DISPUTE / CHARGEBACK NOTIFICATIONS */
    public static function dispute(Payment $payment, Order $order, string $provider, string $stage, ?string $reason = null): void
    {
        $client  = $order->client;
        $fs      = $order->frontSeller ?? $order->seller;
        $pm      = $order->ownerSeller ?? null;

        $admins  = Admin::where('role', 'admin')->get();
        $finance = Admin::where('role', 'finance')->get();

        $delay = 0;

        // CLIENT
        if ($client?->email) {
            Notification::route('mail', $client->email)
                ->notify((new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason))
                    ->delay(now()->addSeconds($delay)));
        }

        // FS
        $delay += 5;
        if ($fs?->email) {
            $fs->notify(
                (new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // PM
        $delay += 5;
        if ($pm?->email && (!$fs || $pm->id !== $fs->id)) {
            $pm->notify(
                (new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // ADMINS
        $delay += 5;
        if ($admins->count()) {
            Notification::send(
                $admins,
                (new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }

        // FINANCE
        $delay += 5;
        if ($finance->count()) {
            Notification::send(
                $finance,
                (new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason))
                    ->delay(now()->addSeconds($delay))
            );
        }
    }

    // public static function dispute(Payment $payment, Order $order, string $provider, string $stage, ?string $reason = null): void
    // {
    //     $client  = $order->client;
    //     $fs      = $order->frontSeller ?? $order->seller;
    //     $pm      = $order->ownerSeller ?? null;

    //     $admins  = Admin::where('role', 'admin')->get();
    //     $finance = Admin::where('role', 'finance')->get();

    //     // CLIENT
    //     if ($client?->email) {
    //         Notification::route('mail', $client->email)
    //             ->notify(new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason));
    //     }

    //     // FRONT SELLER
    //     if ($fs?->email) {
    //         $fs->notify(new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason));
    //     }

    //     // PROJECT MANAGER
    //     if ($pm?->email && (!$fs || $pm->id !== $fs->id)) {
    //         $pm->notify(new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason));
    //     }

    //     // ADMINS
    //     if ($admins->count() > 0) {
    //         Notification::send($admins, new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason));
    //     }

    //     // FINANCE
    //     if ($finance->count() > 0) {
    //         Notification::send($finance, new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason));
    //     }
    // }
}
