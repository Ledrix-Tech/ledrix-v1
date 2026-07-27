<?php

namespace App\Models\Upwork;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Upwork\UpworkPayment;
use App\Models\Upwork\UpworkPaymentLink;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UpworkOrder extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $table = 'upwork_orders';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'brand_id',
        'parent_order_id',
        'order_type',
        'sell_type',
        'service_name',
        'currency',
        'unit_amount',
        'amount_paid',
        'balance_due',
        'status',
        'paid_at',
        'refunded_amount',
        'refund_status',
        'provider_session_id',
        'provider_payment_intent_id',
        'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
        'unit_amount' => 'integer',
        'amount_paid' => 'integer',
        'balance_due' => 'integer',
        'refunded_amount' => 'integer',
    ];

    /** Relationships */

    public function client()
    {
        return $this->belongsTo(UpworkClient::class, 'client_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function parent()
    {
        return $this->belongsTo(UpworkOrder::class, 'parent_order_id');
    }

    public function renewals()
    {
        return $this->hasMany(self::class, 'parent_order_id');
    }

    public function paymentLinks()
    {
        return $this->hasMany(UpworkPaymentLink::class, 'order_id');
    }

    public function latestPaymentLink()
    {
        return $this->hasOne(UpworkPaymentLink::class, 'order_id')->latest();
    }

    public function payments()
    {
        return $this->hasMany(UpworkPayment::class, 'order_id');
    }

    public function recomputeAndPersistStatus(): void
    {
        $this->balance_due = max(0, (int)$this->unit_amount - (int)$this->amount_paid);

        if ($this->balance_due === 0 && (int)$this->unit_amount > 0) {
            $this->status  = 'paid';
            $this->paid_at = $this->paid_at ?: now();
        } elseif ((int)$this->amount_paid > 0) {
            $this->status = 'pending';
        } elseif ($this->status !== 'canceled') {
            $this->status = 'draft';
        }
    }
}
