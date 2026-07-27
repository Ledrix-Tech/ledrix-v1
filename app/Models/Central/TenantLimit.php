<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class TenantLimit extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'package_id',
        'max_admins', 'max_users', 'max_clients', 'max_brands',
        'max_sellers', 'max_leads', 'max_orders',
        'max_payment_links', 'max_payments', 'max_projects', 'max_storage_mb',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package()
    {
        return $this->belongsTo(PackagePricing::class, 'package_id');
    }
}
