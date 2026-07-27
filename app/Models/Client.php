<?php

namespace App\Models;


use Laravel\Sanctum\HasApiTokens;

use Illuminate\Support\Facades\Hash;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'email', 'password', 'phone', 'meta', 'status', 'last_seen'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function isOnline()
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(5));
    }

    public function hasPortalAccess(): bool
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return (bool) ($meta['portal_access'] ?? false);
    }


    // Auto-hash password when setting it
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }


    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class);
    }
}
