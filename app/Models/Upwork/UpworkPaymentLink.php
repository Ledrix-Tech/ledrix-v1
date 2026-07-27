<?php

namespace App\Models\Upwork;

use App\Models\Brand;
use App\Models\Upwork\UpworkPayment;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class UpworkPaymentLink extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $table = 'upwork_payment_links';

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'order_id',
        'client_id',
        'generated_by_id',
        'generated_by_type',
        'service_name',
        'currency',
        'provider',
        'unit_amount',
        'order_total_snapshot',
        'provider_session_id',
        'provider_payment_intent_id',
        'token',
        'status',
        'expires_at',
        'is_active_link',
        'last_issued_url',
        'last_issued_at',
        'last_issued_expires_at',
        'paid_at',
    ];

    protected $casts = [
        'unit_amount' => 'integer',
        'order_total_snapshot' => 'integer',
        'is_active_link' => 'boolean',
        'expires_at' => 'datetime',              // ⚠ your migration uses string - should be timestamp ideally
        'last_issued_at' => 'datetime',
        'last_issued_expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /** Relationships */

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function order()
    {
        // ⚠ your migration: order_id constrained('orders') (PPC orders)
        return $this->belongsTo(UpworkOrder::class, 'order_id');
    }


    public function client()
    {
        // ⚠ your migration: client_id constrained('clients') (PPC clients)
        return $this->belongsTo(UpworkClient::class, 'client_id');
        // If you have upwork_clients:
        // return $this->belongsTo(UpworkClient::class, 'client_id');
    }

    public function generatedBy()
    {
        return $this->morphTo(null, 'generated_by_type', 'generated_by_id');
    }

    public function payments()
    {
        // upwork_payments.payment_link_id should point to upwork_payment_links.id
        return $this->hasMany(UpworkPayment::class, 'payment_link_id');
    }

    public function isActiveLink(): bool
    {
        if (! $this->is_active_link) return false; // manual toggle only
        if ($this->status === 'paid') return false;

        return true;
    }

    public function activePaymentLink()
    {
        return $this->hasOne(UpworkPaymentLink::class, 'order_id')
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
        return route('upwork.paylinks.show', ['token' => $this->token]);
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
            'upwork.paylinks.show',
            now()->addHours($hours),
            ['token' => $this->token, 'p' => $p]
        );
    }
}
