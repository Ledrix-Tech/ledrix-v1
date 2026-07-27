<?php

namespace App\Models\Upwork;

use App\Models\Brand;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class UpworkClient extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $table = 'upwork_clients';

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'name',
        'email',
        'password',
        'phone',
        'meta',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_seen' => 'datetime',
    ];

    /**
     * Auto-hash password when setting it.
     * (If you are NOT using client login for Upwork, you can remove this.)
     */
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = null;
            return;
        }

        // Prevent double-hash
        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    /** ---------- Relations ---------- */

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function orders()
    {
        return $this->hasMany(UpworkOrder::class, 'client_id');
    }

    public function paymentLinks()
    {
        return $this->hasMany(UpworkPaymentLink::class, 'client_id');
    }

    /**
     * Convenience relation: all payments via orders
     */
    public function payments()
    {
        return $this->hasManyThrough(
            UpworkPayment::class,
            UpworkOrder::class,
            'client_id',  // FK on upwork_orders
            'order_id',   // FK on upwork_payments
            'id',         // PK on upwork_clients
            'id'          // PK on upwork_orders
        );
    }

    /** ---------- Helpers ---------- */

    public function isOnline(): bool
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(5));
    }
}
