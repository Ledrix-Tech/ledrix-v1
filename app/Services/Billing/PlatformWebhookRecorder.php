<?php

namespace App\Services\Billing;

use App\Models\Central\PlatformWebhookEvent;
use Throwable;

class PlatformWebhookRecorder
{
    /**
     * Idempotently record and process a platform billing webhook / return callback.
     *
     * @param  callable(PlatformWebhookEvent): void  $handler
     */
    public function recordAndProcess(
        string $provider,
        string $eventId,
        string $eventType,
        array $payload,
        ?int $tenantId,
        callable $handler,
    ): PlatformWebhookEvent {
        if (PlatformWebhookEvent::alreadyHandled($eventId)) {
            return PlatformWebhookEvent::query()
                ->where('event_id', $eventId)
                ->where('status', 'processed')
                ->firstOrFail();
        }

        $event = PlatformWebhookEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'tenant_id'  => $tenantId,
                'provider'   => $provider,
                'event_type' => $eventType,
                'payload'    => $payload,
                'status'     => 'pending',
                'attempts'   => 0,
            ]
        );

        if ($event->isProcessed()) {
            return $event;
        }

        if ($tenantId && ! $event->tenant_id) {
            $event->update(['tenant_id' => $tenantId]);
        }

        try {
            $handler($event);
            $event->refresh();
            // Handler may call markIgnored() / markProcessed() itself.
            if ($event->status === 'pending') {
                $event->markProcessed();
            }
        } catch (Throwable $e) {
            $event->markFailed($e->getMessage());
            throw $e;
        }

        return $event->fresh();
    }
}
