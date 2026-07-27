<?php

namespace App\Models\Central;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * SuperAdmin
 */
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperAdmin extends Authenticatable
{
    use Notifiable;

    protected $connection = 'central';
    protected $table      = 'super_admins';
    protected $guard      = 'super_admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'last_seen',
        'last_login_ip',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    // Tickets assigned to this super admin
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(PlatformSupportTicket::class, 'assigned_to');
    }

    // Announcements this super admin created
    public function announcements(): HasMany
    {
        return $this->hasMany(SystemAnnouncement::class, 'created_by');
    }

    // Limit overrides this super admin applied
    public function limitOverrides(): HasMany
    {
        return $this->hasMany(TenantLimitOverride::class, 'overridden_by');
    }

    // Feature overrides this super admin applied
    public function featureOverrides(): HasMany
    {
        return $this->hasMany(TenantFeatureOverride::class, 'overridden_by');
    }

    // All audit logs where this super admin was the actor
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')
                    ->where('actor_type', 'super_admin');
    }

    // ── Role Helpers ───────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isSupport(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'support']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ── Activity Tracking ──────────────────────────────────

    public function markSeen(): void
    {
        $this->update([
            'last_seen'      => now(),
            'last_login_ip'  => request()->ip(),
        ]);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}