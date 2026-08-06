<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAnnouncementDismissal extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_announcement_dismissals';

    // Only dismissed_at timestamp, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'announcement_id',
        'dismissed_at',
    ];

    protected $casts = [
        'dismissed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(SystemAnnouncement::class);
    }

    // ── Static Helper ──────────────────────────────────────

    // Usage: TenantAnnouncementDismissal::dismiss($tenantId, $announcementId)
    public static function dismiss(
        int $tenantId,
        int $announcementId
    ): void {
        static::firstOrCreate(
            [
                'tenant_id'       => $tenantId,
                'announcement_id' => $announcementId,
            ],
            ['dismissed_at' => now()]
        );
    }
}