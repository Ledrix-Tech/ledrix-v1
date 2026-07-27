<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TenantPasswordReset extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_password_resets';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'created_at',
    ];

    // Token hidden — never expose hashed token in responses
    protected $hidden = ['token'];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // ── Helpers ────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    // Validate a plain token against stored hash
    public function isValidToken(string $plainToken): bool
    {
        return !$this->isExpired()
            && Hash::check($plainToken, $this->token);
    }

    // Generate reset token
    // Returns plain token — show once, send via email
    // Stores hashed token in DB
    public static function generate(
        string $email,
        int    $expiresInMinutes = 60
    ): string {
        // Remove any existing token for this email
        static::where('email', $email)->delete();

        $plain = Str::random(64);

        static::create([
            'email'      => $email,
            'token'      => Hash::make($plain),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'created_at' => now(),
        ]);

        return $plain;
    }

    // Find valid record by email
    // Usage: TenantPasswordReset::findValid($email, $plainToken)
    public static function findValid(
        string $email,
        string $plainToken
    ): ?self {
        $record = static::where('email', $email)
                        ->where('expires_at', '>', now())
                        ->first();

        if (!$record) return null;
        if (!Hash::check($plainToken, $record->token)) return null;

        return $record;
    }

    // Reset password and clean up token
    public static function resetPassword(
        string $email,
        string $plainToken,
        string $newPassword
    ): bool {
        $record = static::findValid($email, $plainToken);
        if (!$record) return false;

        // Update tenant password
        Tenant::where('email', $email)
              ->update(['password' => Hash::make($newPassword)]);

        // Delete used token
        $record->delete();

        return true;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email)
                     ->where('expires_at', '>', now());
    }
}