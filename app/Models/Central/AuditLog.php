<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $connection = 'central';
    protected $table      = 'audit_logs';

    // Audit logs are immutable — no updated_at ever
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'before',
        'after',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Static Logger ──────────────────────────────────────
    // Single entry point for logging from anywhere in the app
    //
    // Usage examples:
    // AuditLog::record('plan.changed', $tenant->id, 'super_admin', $admin->id, $admin->name, [
    //     'before' => ['plan' => 'growth'],
    //     'after'  => ['plan' => 'agency'],
    // ]);
    //
    // AuditLog::record('tenant.suspended', $tenant->id, 'super_admin', $admin->id, $admin->name, [
    //     'subject_type' => 'tenant',
    //     'subject_id'   => $tenant->id,
    //     'description'  => 'Suspended for non-payment',
    // ]);
    //
    // AuditLog::record('lead.created', $tenant->id, 'seller', $seller->id, $seller->name);

    public static function record(
        string  $action,
        ?int    $tenantId  = null,
        string  $actorType = 'system',
        ?int    $actorId   = null,
        ?string $actorName = null,
        array   $context   = []
    ): self {
        return static::create([
            'tenant_id'    => $tenantId,
            'actor_type'   => $actorType,
            'actor_id'     => $actorId,
            'actor_name'   => $actorName,
            'action'       => $action,
            'subject_type' => $context['subject_type'] ?? null,
            'subject_id'   => $context['subject_id']   ?? null,
            'description'  => $context['description']  ?? null,
            'before'       => $context['before']        ?? null,
            'after'        => $context['after']         ?? null,
            'ip_address'   => request()?->ip(),
            'user_agent'   => request()?->userAgent(),
            'created_at'   => now(),
        ]);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActor($query, string $type, int $id)
    {
        return $query->where('actor_type', $type)
                     ->where('actor_id', $id);
    }

    public function scopeBySubject($query, string $type, int $id)
    {
        return $query->where('subject_type', $type)
                     ->where('subject_id', $id);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}