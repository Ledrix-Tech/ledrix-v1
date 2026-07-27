<?php

namespace App\Models\Upwork;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UpworkPayment extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $table = 'upwork_payments';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'payment_link_id',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_payment_intent_id',
        'payload',
        'refunded_amount',
        'refund_status',
        'refund_payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'refund_payload' => 'array',
        'amount' => 'integer',
        'refunded_amount' => 'integer',
    ];

    /** Relationships */

    public function order()
    {
        // ⚠ your migration: order_id constrained('orders') (PPC orders)
        return $this->belongsTo(UpworkOrder::class, 'order_id');
        // If should be upwork_orders:
        // return $this->belongsTo(UpworkOrder::class, 'order_id');
    }

    public function paymentLink()
    {
        // ⚠ your migration: payment_link_id constrained('payment_links') (PPC payment_links)
        return $this->belongsTo(UpworkPaymentLink::class, 'payment_link_id');
        // If should be upwork_payment_links:
        // return $this->belongsTo(UpworkPaymentLink::class, 'payment_link_id');
    }
}
