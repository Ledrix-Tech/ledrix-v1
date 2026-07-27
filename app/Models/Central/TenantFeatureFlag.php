<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class TenantFeatureFlag extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'feature_key', 'is_enabled',
        'enabled_from', 'enabled_until', 'meta',
    ];

    protected $casts = [
        'is_enabled'    => 'boolean',
        'enabled_from'  => 'date',
        'enabled_until' => 'date',
        'meta'          => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        if (!$this->is_enabled) return false;
        $now = now()->toDateString();
        if ($this->enabled_from && $this->enabled_from > $now) return false;
        if ($this->enabled_until && $this->enabled_until < $now) return false;
        return true;
    }
}
