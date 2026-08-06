<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    protected $connection = 'central';

    protected $table = 'demo_requests';

    protected $fillable = [
        'tenant_id',
        'name',
        'company',
        'email',
        'description',
        'status',
        'demo_sent_at',
        'demo_expires_at',
    ];

    protected $casts = [
        'demo_sent_at'    => 'datetime',
        'demo_expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
