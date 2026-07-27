<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSupportReply extends Model
{
    protected $connection = 'central';
    protected $table      = 'platform_support_replies';

    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_id',
        'message',
        'attachment_path',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PlatformSupportTicket::class, 'ticket_id');
    }

    // Dynamic sender resolution
    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'sender_id');
    }

    public function senderTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'sender_id');
    }

    // Resolve correct sender model based on sender_type
    public function getSenderAttribute()
    {
        return $this->sender_type === 'super_admin'
            ? $this->senderAdmin
            : $this->senderTenant;
    }

    public function getSenderNameAttribute(): string
    {
        return $this->sender?->name ?? 'Unknown';
    }

    // ── Helpers ────────────────────────────────────────────

    public function isFromTenant(): bool
    {
        return $this->sender_type === 'tenant';
    }

    public function isFromSupport(): bool
    {
        return $this->sender_type === 'super_admin';
    }

    public function isInternal(): bool
    {
        return (bool) $this->is_internal;
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeFromTenant($query)
    {
        return $query->where('sender_type', 'tenant');
    }
}