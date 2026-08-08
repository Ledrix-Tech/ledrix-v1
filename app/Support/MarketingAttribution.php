<?php

namespace App\Support;

use Illuminate\Http\Request;

class MarketingAttribution
{
    public const SESSION_KEY = 'marketing_attribution';

    /** @var list<string> */
    private const KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
    ];

    public static function capture(Request $request): void
    {
        $incoming = [];

        foreach (self::KEYS as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                $incoming[$key] = substr(trim($value), 0, 255);
            }
        }

        if ($incoming === []) {
            return;
        }

        $existing = session(self::SESSION_KEY, []);
        if (! is_array($existing)) {
            $existing = [];
        }

        session([self::SESSION_KEY => array_merge($existing, $incoming)]);
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        $data = session(self::SESSION_KEY, []);

        return is_array($data) ? array_filter($data, fn ($v) => is_string($v) && $v !== '') : [];
    }

    public static function landingPath(?string $fallback = null): ?string
    {
        $path = session('marketing_landing_path');

        return is_string($path) && $path !== '' ? $path : $fallback;
    }

    public static function rememberLandingPath(string $path): void
    {
        if (! session()->has('marketing_landing_path')) {
            session(['marketing_landing_path' => substr($path, 0, 255)]);
        }
    }

    public static function summaryLine(): string
    {
        $parts = self::all();
        if ($parts === []) {
            return '';
        }

        return collect($parts)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode(' | ');
    }
}
