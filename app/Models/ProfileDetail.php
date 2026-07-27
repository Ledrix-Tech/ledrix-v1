<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Illuminate\Notifications\Notifiable;

class ProfileDetail extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',        // e.g. "App\Models\Admin" or "App\Models\Seller"
        'profile',          // profile picture
        'name',             // full name
        'email',
        'alternate_email',
        'phone',
        'address',
        'status',
    ];
    
    public function sellers()
    {
        return $this->belongsTo(Seller::class);
    }

    public function clients()
    {
        return $this->belongsTo(Client::class);
    }

    public function admins()
    {
        return $this->belongsTo(Admin::class);
    }
}
