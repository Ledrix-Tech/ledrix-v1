<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use BelongsToTenant, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'brand_id',
        'client_id',
        'name',
        'email',
        'phone',
        'service',
        'message',
        'status',        // new|contacted|qualified|client|disqualified
        'prediction',
        'converted_at',
        'domain_url',    // normalized host
        'meta',
        'is_finish',          // json: service, utms, referrer, session_id, ip, ua, idem...
        'auto_replied'
    ];

    protected $casts = [
        'is_finish' => 'boolean',
        'meta'         => 'array',
        'prediction'   => 'array',
        'converted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::deleting(function ($lead) {
            // If it's a soft delete, related models should also be soft deleted
            if ($lead->isForceDeleting()) {
                // Permanent delete
                $lead->assignments()->forceDelete();
                $lead->orders()->forceDelete();
                $lead->paymentLinks()->forceDelete();
            } else {
                // Soft delete
                $lead->assignments()->delete();
                $lead->orders()->delete();
                $lead->paymentLinks()->delete();
            }
        });
    }

    // relations
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

    public function assignments()
    {
        return $this->hasMany(LeadAssignment::class, 'lead_id');
    }

    public function latestAssignment()
    {
        return $this->hasOne(LeadAssignment::class, 'lead_id')->latestOfMany();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'lead_id', 'id');
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function ordersForLead()
    {
        // All orders for the same client+brand as this lead
        return $this->hasMany(Order::class, 'client_id', 'client_id')
            ->where('orders.brand_id', $this->brand_id);
    }

    public function latestOrder()
    {
        // Most recent order for this lead’s client+brand
        return $this->hasOne(Order::class, 'client_id', 'client_id')
            ->where('orders.brand_id', $this->brand_id)
            // ->where('orders.order_type', 'original')
            ->latestOfMany();   // requires Laravel 9+. If on older, see note below.
    }

    // light normalization
    protected function email(): Attribute
    {
        return Attribute::make(set: fn($v) => $v ? strtolower(trim($v)) : null);
    }
    protected function domainUrl(): Attribute
    {
        return Attribute::make(set: fn($v) => self::normalizeHost($v));
    }
    public static function normalizeHost(?string $url): ?string
    {
        if (!$url) return null;
        if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
        $h = parse_url($url, PHP_URL_HOST);
        return $h ? strtolower(preg_replace('/^www\./i', '', $h)) : null;
    }
}
