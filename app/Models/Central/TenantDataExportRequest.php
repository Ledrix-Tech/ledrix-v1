<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TenantDataExportRequest extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_data_export_requests';

    protected $fillable = [
        'tenant_id',
        'requested_by_admin_id',
        'requested_by_name',
        'requested_by_type',
        'reason',
        'status',
        'rejection_note',
        'reviewed_by',
        'reviewed_at',
        'file_path',
        'file_size',
        'ready_at',
        'expires_at',
        'download_count',
        'last_downloaded_at',
        'meta',
    ];

    protected $casts = [
        'reviewed_at'         => 'datetime',
        'ready_at'            => 'datetime',
        'expires_at'          => 'datetime',
        'last_downloaded_at'  => 'datetime',
        'meta'                => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->file_path;
    }

    public function tenantLinkExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function tenantCanDownload(): bool
    {
        return $this->isReady() && ! $this->tenantLinkExpired();
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'approved', 'processing'], true);
    }

    public static function inProgressForTenant(int $tenantId): ?self
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->latest()
            ->first();
    }

    public function absolutePath(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('local')->path($this->file_path);
    }
}
