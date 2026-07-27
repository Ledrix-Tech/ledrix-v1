<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformWebhookEvent extends Model
{
    protected $connection = 'central';
    protected $table      = 'platform_webhook_events';

    protected $fillable = [
        'tenant_id',
        'provider',
        'event_id',
        'event_type',
        'payload',
        'status',
        'processed_at',
        'attempts',
        'error_message',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Idempotency Check ──────────────────────────────────

    // Call this BEFORE processing any incoming webhook
    // If returns true — skip processing, already done
    // Usage: PlatformWebhookEvent::alreadyHandled($stripeEventId)
    public static function alreadyHandled(string $eventId): bool
    {
        return static::where('event_id', $eventId)
                     ->where('status', 'processed')
                     ->exists();
    }

    // ── Status Helpers ─────────────────────────────────────

    public function markProcessed(): void
    {
        $this->update([
            'status'       => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->increment('attempts');
        $this->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);
    }

    public function markIgnored(): void
    {
        $this->update(['status' => 'ignored']);
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function canRetry(int $maxAttempts = 3): bool
    {
        return $this->status === 'failed'
            && $this->attempts < $maxAttempts;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRetryable($query, int $maxAttempts = 3)
    {
        return $query->where('status', 'failed')
                     ->where('attempts', '<', $maxAttempts);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}