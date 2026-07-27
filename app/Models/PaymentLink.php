<?php

namespace App\Models;


use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentLink extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'seller_id',
        'brand_id',
        'client_id',
        'order_id',
        'service_name',
        'currency',
        'unit_amount',
        'order_total_snapshot',
        'token',
        'status',
        'expires_at',
        'is_active_link',
        'provider_session_id',
        'provider_payment_intent_id',
        'last_issued_url',
        'last_issued_at',
        'last_issued_expires_at',
        'paid_at',
        'provider_receipt_url',
        'payment_card_brand',
        'provider',
        'owner_seller_id',
        'generated_by_id',
        'generated_by_type',
        'credit_to_seller_id'
    ];

    protected $casts = [
        'unit_amount' => 'integer',
        'expires_at'  => 'datetime',
        'paid_at'     => 'datetime',
    ];

    public function generatedBy()
    {
        return $this->morphTo(null, 'generated_by_type', 'generated_by_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // public function isActive(): bool
    // {
    //     if ($this->status !== 'active') return false;
    //     if ($this->expires_at && now()->greaterThan($this->expires_at)) return false;
    //     return true;
    // }

    public function isActiveLink(): bool
    {
        if (! $this->is_active_link) {
            return false;
        }

        if ($this->status === 'paid') {
            return false;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function activePaymentLink()
    {
        return $this->hasOne(PaymentLink::class, 'order_id')
            ->where('is_active_link', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest(); // in case multiple active links exist
    }


    public function markAsUsed()
    {
        $this->update([
            'status'         => 'paid',
            'is_active_link' => false,
            'expires_at'     => now(),
            'paid_at'        => now(),
        ]);
    }

    public function getUrlAttribute(): string
    {
        // plain (unsiged) URL if you ever need it
        return route('paylinks.show', ['token' => $this->token]);
    }

    public function signedUrl(int $hours = 168): string
    {
        $payload = [
            't'   => $this->token,
            'a'   => $this->unit_amount,
            'c'   => $this->currency,
            's'   => $this->service_name,
            'exp' => optional($this->expires_at)?->timestamp,
        ];
        $p = Crypt::encryptString(json_encode($payload));

        return URL::temporarySignedRoute(
            'paylinks.show',
            now()->addHours($hours),
            ['token' => $this->token, 'p' => $p]
        );
    }
}
