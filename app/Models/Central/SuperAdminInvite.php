<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SuperAdminInvite extends Model
{
    protected $connection = 'central';

    protected $table = 'super_admin_invites';

    protected $fillable = [
        'token',
        'name',
        'email',
        'role',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public static function issue(array $data, ?int $invitedBy = null, int $hoursValid = 48): self
    {
        do {
            $token = Str::random(64);
        } while (static::query()->where('token', $token)->exists());

        return static::query()->create([
            'token'      => $token,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'role'       => $data['role'],
            'invited_by' => $invitedBy,
            'expires_at' => now()->addHours($hoursValid),
        ]);
    }

    public function markAccepted(): void
    {
        $this->update(['accepted_at' => now()]);
    }
}
