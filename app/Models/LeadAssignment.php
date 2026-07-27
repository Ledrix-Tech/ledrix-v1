<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadAssignment extends Model
{
    use BelongsToTenant, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'assigned_to',
        'assigned_role',
        'assigned_by',
        'assigned_at',
        'status'
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee()
    {
        return $this->belongsTo(Seller::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(Seller::class, 'assigned_by');
    }
}
