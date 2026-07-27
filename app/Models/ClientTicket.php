<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientTicket extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'client_id',
        'seller_id',
        'order_id',
        'subject',
        'description',
        'attachment',
        'priority',
        'status',
        'source',
        'is_client_visible',
        'is_internal',
        'closed_at',
        'closed_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
