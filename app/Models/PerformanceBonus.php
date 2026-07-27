<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PerformanceBonus extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'brand_id',
        'target_revenue',
        'bonus_amount',
        'period_start',
        'period_end',
        'currency',
        'status'
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
