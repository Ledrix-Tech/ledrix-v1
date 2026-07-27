<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Notifications\Notifiable;

class ProjectTask extends Model
{
    use BelongsToTenant, Notifiable;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'title',
        'description',
        'assigned_to',
        'status',
        'due_date',
        'priority',
        'meta',
        'status',
    ];

    protected $casts = [
        'meta' => 'array',
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedSeller()
    {
        return $this->belongsTo(Seller::class, 'assigned_to');
    }
}
