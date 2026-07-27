<?php

namespace App\Models\Central;

use App\Models\Central\PlatformSupportReply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{ BelongsTo, HasMany, HasOne };

class PlatformSupportTicket extends Model
{
    protected $connection = 'central';
    protected $table      = 'platform_support_tickets';

    protected $fillable = [
        'tenant_id', 'assigned_to',
        'subject', 'description',
        'category', 'priority', 'status',
        'first_replied_at', 'resolved_at', 'closed_at',
        'meta',
    ];

    protected $casts = [
        'first_replied_at' => 'datetime',
        'resolved_at'      => 'datetime',
        'closed_at'        => 'datetime',
        'meta'             => 'array',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PlatformSupportReply::class, 'ticket_id');
    }

    // Public replies — visible to tenant
    public function publicReplies(): HasMany
    {
        return $this->hasMany(PlatformSupportReply::class, 'ticket_id')
                    ->where('is_internal', false);
    }

    // Internal notes — visible to super admins only
    public function internalNotes(): HasMany
    {
        return $this->hasMany(PlatformSupportReply::class, 'ticket_id')
                    ->where('is_internal', true);
    }

    public function latestReply(): HasOne
    {
        return $this->hasOne(PlatformSupportReply::class, 'ticket_id')
                    ->latestOfMany();
    }

    // ── Helpers ────────────────────────────────────────────

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function assign(int $superAdminId): void
    {
        $this->update([
            'assigned_to' => $superAdminId,
            'status'      => 'in_progress',
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function hold(): void
    {
        $this->update(['status' => 'on_hold']);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}