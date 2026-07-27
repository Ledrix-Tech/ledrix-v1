<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Notifications\Notifiable;

class Brand extends Model
{
    use BelongsToTenant, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'module',
        'brand_name',
        'brand_url',
        'brand_host',        // <- add this column
        'allowed_origins',   // JSON array of hosts
        'public_form_token',
        'webhook_secret',
        'require_hmac',
        'lead_script',
        'field_mapping',
        'status',            // optional
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'allowed_origins' => 'array',
        'require_hmac'    => 'boolean',
    ];

    // never leak secrets in API responses/logs
    protected $hidden = [
        'webhook_secret',
        'public_form_token',
    ];

    /* ---------------------------------
     | Model events
     * --------------------------------- */
    protected static function booted()
    {
        static::creating(function (Brand $brand) {
            // generate public + secret tokens if missing
            if (empty($brand->public_form_token)) {
                $brand->public_form_token = bin2hex(random_bytes(24)); // 48 chars
            }
            if (empty($brand->webhook_secret)) {
                $brand->webhook_secret = bin2hex(random_bytes(32)); // 64 chars
            }

            // derive host + origins from brand_url if not set
            if (!$brand->brand_host && $brand->brand_url) {
                $brand->brand_host = self::normalizeHost($brand->brand_url);
            }
            // seed allowed_origins with host + www.host
            if (empty($brand->allowed_origins)) {
                $host = $brand->brand_host ?: ($brand->brand_url ? self::normalizeHost($brand->brand_url) : null);
                if ($host) {
                    $brand->allowed_origins = array_values(array_unique([$host, 'www.' . $host]));
                }
            }
        });

        static::saving(function (Brand $brand) {
            // keep host + origins in sync if url changed
            if ($brand->isDirty('brand_url') && $brand->brand_url) {
                $host = self::normalizeHost($brand->brand_url);
                if (!$brand->brand_host) {
                    $brand->brand_host = $host;
                }
                // ensure host variants are present
                $origins = collect((array) $brand->allowed_origins);
                $origins = $origins->merge([$host, 'www.' . $host])->filter()->unique()->values();
                $brand->allowed_origins = $origins->all();
            }
        });
    }

    /* ---------------------------------
     | Accessors / Mutators
     * --------------------------------- */
    public function setBrandUrlAttribute($value): void
    {
        $v = trim((string) $value);
        // store as given (for display), but normalize host elsewhere
        $this->attributes['brand_url'] = $v;
        // keep brand_host in sync if empty
        if (empty($this->attributes['brand_host']) && $v) {
            $this->attributes['brand_host'] = self::normalizeHost($v);
        }
    }

    public function setAllowedOriginsAttribute($value): void
    {
        // accept URLs or hosts, store as bare hosts (no scheme)
        $hosts = collect((array) $value)
            ->map(fn($v) => self::normalizeHost((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->attributes['allowed_origins'] = json_encode($hosts, JSON_UNESCAPED_SLASHES);
    }

    public function accountKeys()
    {
        return $this->hasOne(AccountKey::class);
    }

    /* ---------------------------------
     | Relationships
     * --------------------------------- */
    public function sellers()
    {
        return $this->hasMany(Seller::class, 'brand_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'brand_id');
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class);
    }

    /* ---------------------------------
     | Scopes & Resolvers
     * --------------------------------- */
    public function scopeWhereHost($q, string $host)
    {
        $h = self::normalizeHost($host);
        return $q->where('brand_host', $h)
            ->orWhereJsonContains('allowed_origins', $h)
            ->orWhereJsonContains('allowed_origins', 'www.' . $h);
    }

    public static function resolveFromUrl(?string $url): ?self
    {
        $h = self::normalizeHost($url);
        return $h ? self::query()->whereHost($h)->first() : null;
    }

    public static function resolveFromOrigin(\Illuminate\Http\Request $r): ?self
    {
        $origin = $r->headers->get('Origin') ?: $r->headers->get('Referer');
        return self::resolveFromUrl($origin);
    }

    /* ---------------------------------
     | HMAC helpers
     * --------------------------------- */
    public function sign(string $json): string
    {
        return hash_hmac('sha256', $json, (string) $this->webhook_secret);
    }

    public function verify(string $json, ?string $signature): bool
    {
        if (!$signature) return false;
        $expected = $this->sign($json);
        return hash_equals($expected, $signature);
    }

    /* ---------------------------------
     | Utils
     * --------------------------------- */
    public static function normalizeHost(?string $url): ?string
    {
        if (!$url) return null;
        // allow either a bare host or a full URL
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        $h = parse_url($url, PHP_URL_HOST);
        return $h ? strtolower(preg_replace('/^www\./i', '', $h)) : null;
    }
}
