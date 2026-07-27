<?php

namespace App\Models;

use App\Models\Brand;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Seller extends Authenticatable
{
    use BelongsToTenant, HasFactory, Notifiable;

    protected $fillable = ['tenant_id', 'brand_id', 'name', 'sudo_name', 'is_seller', 'email', 'password', 'status', 'last_seen'];

    protected $hidden = ['password', 'remember_token'];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function isOnline()
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(5));
    }

    // Auto-hash password when setting it
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
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


    // Lead assignments
    public function currentLeadIds()
    {
        // returns a Collection of lead IDs owned by this seller now (assignment-aware)
        $latestAssignments = DB::table('lead_assignments')
            ->select('lead_id', 'assigned_to')
            ->whereIn('id', function ($q) {
                $q->from('lead_assignments as la2')
                    ->select(DB::raw('MAX(la2.id)'))
                    ->groupBy('la2.lead_id');
            });

        $assigned = Lead::joinSub($latestAssignments, 'la', function ($join) {
            $join->on('la.lead_id', '=', 'leads.id');
        })
            ->where('la.assigned_to', $this->id)
            ->pluck('leads.id');

        $direct  = Lead::where('seller_id', $this->id)->pluck('id');

        return $assigned->merge($direct)->unique();
    }

    public function currentLeads()
    {
        return Lead::whereIn('id', $this->currentLeadIds());
    }

    public function ordersByCurrentLeads()
    {
        return Order::whereIn('lead_id', $this->currentLeadIds());
    }
}
