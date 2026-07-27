<?php

namespace App\Models;


use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\SendBriefLinkMail;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'brand_id',
        'seller_id',
        'client_id',
        'service_name',
        'currency',
        'unit_amount',
        'status',
        'provider_session_id',
        'provider_payment_intent_id',
        'paid_at',
        'amount_paid',
        'balance_due',
        'refunded_amount',
        'refund_status',
        'buyer_name',
        'buyer_email',
        'provider_receipt_url',
        'payment_card_brand',
        // new fields
        'owner_seller_id',
        'opened_by_seller_id',
        'front_seller_id',
        'front_credits_used',
        'front_credited_cents',
        'first_paid_at',
        'parent_order_id',
        'order_type'
    ];

    protected static function booted()
    {
        static::saving(function (Order $o) {
            $pmStatuses = ['in_progress', 'revision', 'completed'];

            if (in_array($o->getOriginal('status'), $pmStatuses)) {
                // Still recompute balance but DO NOT auto-change status
                $o->balance_due = max(0, (int)$o->unit_amount - (int)$o->amount_paid);

                // If fully paid for first time, set paid_at but DO NOT override status
                if ($o->balance_due === 0 && !$o->paid_at) {
                    $o->paid_at = now();
                }

                return; // ← exit early, avoid overriding status
            }

            $o->balance_due = max(0, (int)$o->unit_amount - (int)$o->amount_paid);

            if ($o->balance_due === 0 && $o->unit_amount > 0) {
                $o->status  = 'paid';
                $o->paid_at = $o->paid_at ?? now();
            } elseif ($o->amount_paid > 0 && $o->status !== 'paid') {
                $o->status = 'pending';
            } elseif ($o->status !== 'canceled') {
                $o->status = 'draft';
            }
        });
        // static::created(function ($order) {
        //     // Create brief
        //     $brief = Questionnair::create([
        //         'client_id' => $order->client_id,
        //         'order_id'  => $order->id,
        //         'service_name' => $order->service_name,
        //         'meta' => [],
        //         'status' => 'pending',
        //         'brief_token' => \Illuminate\Support\Str::uuid(),
        //         'brief_token_expires_at' => now()->addDays(14),
        //     ]);
        //     // Load client + brand
        //     $client = $order->client;
        //     $brandName = $order->brand->brand_name ?? 'N/A';
        //     // Brief URL
        //     $briefUrl = route('brief.show', ['token' => $brief->brief_token]);
        //     // Send email
        //     if ($client) {
        //         $client->notify(new \App\Notifications\SendBriefLinkMail(
        //             $client,
        //             $order,
        //             $brandName,
        //             $briefUrl
        //         ));
        //     }
        // });
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

    public function brief()
    {
        return $this->hasOne(Questionnair::class, 'order_id', 'id');
    }

    public function tickets()
    {
        return $this->hasMany(ClientTicket::class);
    }

    public function original()
    {
        return $this->order_type === 'original'
            ? $this
            : Order::find($this->parent_order_id);
    }

    public function latestPaymentLink()
    {
        return $this->hasOne(PaymentLink::class, 'order_id')->latest();
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class,);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', ['draft', 'pending']);
    }
    public function canCharge(int $amount): bool
    {
        return $amount > 0 && $amount <= (int)$this->balance_due; // ensure cast to int
    }

    public function canGenerateMoreLinks(): bool
    {
        return (int)$this->balance_due > 0;
    }

    protected $casts = [
        'paid_at' => 'datetime',
        'unit_amount' => 'integer',
        'amount_paid' => 'integer',
        'balance_due' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    // app/Models/Order.php
    public function scopeOwnedBy($q, int $sellerId)
    {
        return $q->where('seller_id', $sellerId);
    }


    // Parent (original) order
    public function parent()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    // Renewal orders linked to this one
    public function renewals()
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }
}
