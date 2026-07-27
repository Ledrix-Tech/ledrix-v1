<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $connection = 'central';
    protected $table      = 'contacts';

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'company_size',
        'inquiry_type',
        'message',
        'status',
        'admin_note',
        'last_contacted_at',
        'replied_at',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'replied_at' => 'datetime',
    ];
}