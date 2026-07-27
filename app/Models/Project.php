<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Notifications\Notifiable;

class Project extends Model
{
    use BelongsToTenant, Notifiable;

    protected $fillable = [
        'tenant_id',
        'title',
        'lead_id',
        'order_id',
        'front_seller_id',
        'owner_seller_id',
        'status',
        'start_date',
        'due_date',
        'description',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function projectManager()
    {
        return $this->belongsTo(Seller::class, 'owner_seller_id');
    }

    public function frontSeller()
    {
        return $this->belongsTo(Seller::class, 'front_seller_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
