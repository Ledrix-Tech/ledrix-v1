<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

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
        'credit_to_seller_id',
        'seller_id',
        'owner_seller_id',
        'front_seller_id',
        'credited_seller_id',
        'refund_status',
        'refunded_amount',
        'refund_payload'
    ];

    protected $guarded = [];               // or fillable for each field
    protected $casts = ['payload' => 'array'];

    protected static function booted()
    {
        static::deleting(function ($order) {
            if ($order->isForceDeleting()) {
                $order->payments()->forceDelete();
            } else {
                $order->payments()->delete();
            }
        });
    }

    public function scopeForSeller($q, Seller $seller)
    {
        return $q->when(
            $seller->is_seller === 'front_seller',
            fn($q) => $q->where('front_seller_id', $seller->id)
        )->when(
            $seller->is_seller === 'project_manager',
            fn($q) => $q->where('owner_seller_id', $seller->id)
        );
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentLink()
    {
        return $this->belongsTo(PaymentLink::class);
    }

    /** Provider stored on the payment row, with payment-link fallback. */
    public function resolvedProvider(): string
    {
        $provider = strtolower(trim((string) ($this->attributes['provider'] ?? '')));

        if ($provider !== '') {
            return $provider;
        }

        $this->loadMissing('paymentLink:id,provider');

        return strtolower(trim((string) ($this->paymentLink?->provider ?? 'unknown')));
    }

    public function isStripe(): bool
    {
        return $this->resolvedProvider() === 'stripe';
    }

    public function isPayPal(): bool
    {
        return $this->resolvedProvider() === 'paypal';
    }

    // app/Models/Payment.php
    public function scopeCreditedTo($q, int $sellerId)
    {
        return $q->where('credit_to_seller_id', $sellerId);
    }
    public function scopePaidStatus($q)
    {
        return $q->where('status', 'succeeded');
    }
}
