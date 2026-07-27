<?php

namespace App\Models\Central;

use App\Models\Central\TenantAnnouncementDismissal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{ BelongsTo, HasMany };

class SystemAnnouncement extends Model
{
    protected $connection = 'central';
    protected $table      = 'system_announcements';

    protected $fillable = [
        'title', 'message', 'type', 'target',
        'is_dismissible', 'show_from', 'show_until',
        'status', 'created_by',
    ];

    protected $casts = [
        'is_dismissible' => 'boolean',
        'show_from'      => 'datetime',
        'show_until'     => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(TenantAnnouncementDismissal::class, 'announcement_id');
    }

    // ── Visibility Helpers ─────────────────────────────────

    public function isCurrentlyVisible(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->show_from  && now()->lt($this->show_from))  return false;
        if ($this->show_until && now()->gt($this->show_until)) return false;
        return true;
    }

    // Full check: active window + plan target + not dismissed
    public function isVisibleToTenant(Tenant $tenant): bool
    {
        if (!$this->isCurrentlyVisible()) return false;

        // Plan targeting
        if ($this->target !== 'all') {
            $expected = 'plan_' . ($tenant->plan?->slug ?? '');
            if ($this->target !== $expected) return false;
        }

        // Already dismissed?
        if ($this->is_dismissible) {
            $dismissed = $this->dismissals()
                              ->where('tenant_id', $tenant->id)
                              ->exists();
            if ($dismissed) return false;
        }

        return true;
    }

    public function dismiss(int $tenantId): void
    {
        TenantAnnouncementDismissal::dismiss($tenantId, $this->id);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeVisible($query)
    {
        return $query->where('status', 'active')
            ->where(fn($q) => $q
                ->whereNull('show_from')
                ->orWhere('show_from', '<=', now()))
            ->where(fn($q) => $q
                ->whereNull('show_until')
                ->orWhere('show_until', '>=', now()));
    }

    public function scopeForPlan($query, string $planSlug)
    {
        return $query->where(fn($q) => $q
            ->where('target', 'all')
            ->orWhere('target', 'plan_' . $planSlug));
    }
}
