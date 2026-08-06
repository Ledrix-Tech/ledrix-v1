<?php

namespace App\Support;

use App\Mail\PlatformOpsAlertMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PlatformOpsNotifier
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function alert(string $type, string $headline, array $context = []): void
    {
        $to = config('services.bank_transfer.notify_email')
            ?: config('mail.from.address');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new PlatformOpsAlertMail($type, $headline, $context));
        } catch (Throwable $e) {
            Log::warning('Platform ops alert mail failed', [
                'type'  => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
