<?php

namespace App\Models;

use App\Casts\LegacyEncryptedString;
use App\Support\AccountKeySecrets;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class AccountKey extends Model
{
    use BelongsToTenant, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'module',
        'brand_id',
        'brand_url',

        // Stripe
        'stripe_publishable_key',
        'stripe_secret_key',
        'stripe_webhook_secret',

        // PayPal
        'paypal_client_id',
        'paypal_secret',
        'paypal_webhook_id',
        'paypal_base_url',

        // Status
        'status',
    ];

    protected $hidden = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
    ];

    protected $casts = [
        'stripe_secret_key'     => LegacyEncryptedString::class,
        'stripe_webhook_secret' => LegacyEncryptedString::class,
        'paypal_secret'         => LegacyEncryptedString::class,
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function hasStripeSecret(): bool
    {
        return filled($this->stripe_secret_key);
    }

    public function hasStripeWebhookSecret(): bool
    {
        return filled($this->stripe_webhook_secret);
    }

    public function hasPaypalSecret(): bool
    {
        return filled($this->paypal_secret);
    }

    public function maskedStripeSecret(): ?string
    {
        return AccountKeySecrets::mask($this->stripe_secret_key);
    }

    public function maskedStripeWebhookSecret(): ?string
    {
        return AccountKeySecrets::mask($this->stripe_webhook_secret);
    }

    public function maskedPaypalSecret(): ?string
    {
        return AccountKeySecrets::mask($this->paypal_secret);
    }

    public static function stripeWebhookSecretForBrand(int $brandId, string $module = 'ppc'): ?string
    {
        $key = static::withoutGlobalScopes()
            ->where('brand_id', $brandId)
            ->where('module', $module)
            ->where('status', 'active')
            ->first();

        return $key?->stripe_webhook_secret;
    }
}
