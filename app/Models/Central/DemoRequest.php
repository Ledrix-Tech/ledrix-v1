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

    /** Parsed from LP form notes, e.g. source=lp_demo */
    public function marketingSource(): ?string
    {
        if (preg_match('/(?:^|\s|·)source=([a-z0-9_-]+)/i', (string) $this->description, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    public function marketingLanding(): ?string
    {
        if (preg_match('/(?:^|\s|·)landing=(\/[^\s·]+)/i', (string) $this->description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function notesWithoutMarketing(): string
    {
        $text = (string) $this->description;
        $text = preg_replace('/\n*\s*\[Marketing\].*$/s', '', $text) ?? $text;

        return trim($text);
    }
}
