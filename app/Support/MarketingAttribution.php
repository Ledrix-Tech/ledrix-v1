<?php

namespace App\Support;

use Illuminate\Http\Request;

class MarketingAttribution
{
    public const SESSION_KEY = 'marketing_attribution';

    public const LANDING_PATH_KEY = 'marketing_landing_path';

    /** Query-string click / campaign IDs from ads and socials. */
    private const QUERY_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'fbclid',
        'gclid',
        'gbraid',
        'wbraid',
        'ttclid',
        'msclkid',
        'li_fat_id',
        'twclid',
        'igshid',
        'sccid',
        'ScCid',
        'epik',
        'rdt_cid',
    ];

    /** First-party cookies set by pixels (must be excluded from Laravel cookie encryption). */
    private const COOKIE_KEYS = [
        '_fbp',
        '_fbc',
        '_ttp',
        '_gcl_aw',
        '_gcl_gs',
    ];

    /** @var array<string, string> */
    private const CLICK_ID_PLATFORMS = [
        'fbclid'    => 'meta',
        'gclid'     => 'google',
        'gbraid'    => 'google',
        'wbraid'    => 'google',
        'ttclid'    => 'tiktok',
        'msclkid'   => 'bing',
        'li_fat_id' => 'linkedin',
        'twclid'    => 'twitter',
        'igshid'    => 'instagram',
        'sccid'     => 'snapchat',
        'ScCid'     => 'snapchat',
        'epik'      => 'pinterest',
        'rdt_cid'   => 'reddit',
    ];

    /** @var array<string, string> */
    private const REFERRER_HOSTS = [
        'facebook.com'   => 'facebook',
        'fb.com'         => 'facebook',
        'l.facebook.com' => 'facebook',
        'instagram.com'  => 'instagram',
        'l.instagram.com'=> 'instagram',
        'tiktok.com'     => 'tiktok',
        'linkedin.com'   => 'linkedin',
        'lnkd.in'        => 'linkedin',
        'twitter.com'    => 'twitter',
        'x.com'          => 'twitter',
        't.co'           => 'twitter',
        'youtube.com'    => 'youtube',
        'youtu.be'       => 'youtube',
        'pinterest.com'  => 'pinterest',
        'pin.it'         => 'pinterest',
        'reddit.com'     => 'reddit',
        'snapchat.com'   => 'snapchat',
        'whatsapp.com'   => 'whatsapp',
        'wa.me'          => 'whatsapp',
        'telegram.org'   => 'telegram',
        't.me'           => 'telegram',
        'threads.net'    => 'threads',
        'google.com'     => 'google',
        'google.com.pk'  => 'google',
        'bing.com'       => 'bing',
        'yahoo.com'      => 'yahoo',
        'duckduckgo.com' => 'duckduckgo',
    ];

    /** @var list<string> */
    private const PAID_MEDIUMS = ['cpc', 'ppc', 'paid', 'paidsocial', 'paid_social', 'cpm', 'display'];

    /** @var list<string> */
    private const SEARCH_NETWORKS = ['google', 'bing', 'yahoo', 'duckduckgo'];

    public static function capture(Request $request): void
    {
        if (! $request->isMethod('GET') || ! self::isPublicFrontRequest($request)) {
            return;
        }

        $incoming = [];

        foreach (self::QUERY_KEYS as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                $incoming[$key] = self::clip($value);
            }
        }

        foreach (self::COOKIE_KEYS as $key) {
            $value = $request->cookie($key);
            if (is_string($value) && trim($value) !== '') {
                $incoming[$key] = self::clip($value);
            }
        }

        if (! empty($incoming['fbclid']) && empty($incoming['_fbc'])) {
            $incoming['_fbc'] = 'fb.1.'.time().'.'.$incoming['fbclid'];
        }

        $referrer = (string) $request->headers->get('referer', '');
        $referrerHost = self::referrerHost($referrer, $request);
        if ($referrerHost !== null) {
            $incoming['referrer'] = self::clip($referrer);
            $incoming['referrer_host'] = self::clip($referrerHost);
        }

        $existing = session(self::SESSION_KEY, []);
        if (! is_array($existing)) {
            $existing = [];
        }

        foreach ($incoming as $key => $value) {
            if (! isset($existing[$key]) || $existing[$key] === '') {
                $existing[$key] = $value;
            }
        }

        if ($existing !== []) {
            session([self::SESSION_KEY => $existing]);
        }

        self::rememberLandingPath($request->getPathInfo() ?: '/');
    }

    public static function isPublicFrontRequest(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        if ($path === '') {
            return true;
        }

        foreach ([
            'admin',
            'seller',
            'client',
            'super-admin',
            'upwork',
            'livewire',
            'storage',
            'vendor',
            'horizon',
            'telescope',
            'api',
            'webhooks',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        $data = session(self::SESSION_KEY, []);

        return is_array($data) ? array_filter($data, fn ($v) => is_string($v) && $v !== '') : [];
    }

    public static function landingPath(?string $fallback = null): ?string
    {
        $path = session(self::LANDING_PATH_KEY);

        return is_string($path) && $path !== '' ? $path : $fallback;
    }

    public static function rememberLandingPath(string $path): void
    {
        if (! session()->has(self::LANDING_PATH_KEY)) {
            session([self::LANDING_PATH_KEY => substr($path, 0, 255)]);
        }
    }

    /**
     * Classified origin: paid_{platform} | organic_{network} | referral | direct.
     */
    public static function source(): string
    {
        $data = self::all();

        $utmSource = self::normalizeSource($data['utm_source'] ?? '');
        $utmMedium = strtolower((string) ($data['utm_medium'] ?? ''));

        foreach (self::CLICK_ID_PLATFORMS as $param => $platform) {
            if (! empty($data[$param])) {
                $label = $utmSource !== '' ? $utmSource : $platform;

                return 'paid_'.$label;
            }
        }

        if ($utmSource !== '') {
            $prefix = in_array($utmMedium, self::PAID_MEDIUMS, true) || $utmMedium === ''
                ? 'paid_'
                : 'utm_';

            if (in_array($utmMedium, ['organic', 'social', 'referral'], true)) {
                return $utmMedium.'_'.$utmSource;
            }

            return $prefix.$utmSource;
        }

        $host = strtolower((string) ($data['referrer_host'] ?? ''));
        if ($host !== '') {
            $network = self::networkFromHost($host);
            if ($network !== null) {
                if (in_array($network, self::SEARCH_NETWORKS, true)) {
                    return 'organic_search';
                }

                return 'organic_'.$network;
            }

            return 'referral';
        }

        return 'direct';
    }

    public static function conversionSource(): string
    {
        $source = self::source();

        return $source === 'direct' ? 'registration' : $source;
    }

    public static function summaryLine(): string
    {
        $parts = self::all();
        if ($parts === []) {
            return '';
        }

        $ordered = array_merge(['source' => self::source()], $parts);

        return collect($ordered)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode(' | ');
    }

    private static function clip(string $value): string
    {
        return substr(trim($value), 0, 255);
    }

    private static function referrerHost(string $referrer, Request $request): ?string
    {
        if ($referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        $own = strtolower((string) parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST));

        if ($own !== '' && ($host === $own || str_ends_with($host, '.'.$own))) {
            return null;
        }

        return $host;
    }

    private static function networkFromHost(string $host): ?string
    {
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        foreach (self::REFERRER_HOSTS as $suffix => $network) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return $network;
            }
        }

        return null;
    }

    private static function normalizeSource(string $source): string
    {
        $source = strtolower(trim($source));

        return match ($source) {
            'fb', 'facebook', 'ig', 'instagram', 'meta_ads', 'facebook_ads' => 'meta',
            'google_ads', 'adwords' => 'google',
            'x', 'twitter.com' => 'twitter',
            default => $source,
        };
    }

    public static function fieldLabel(string $key): string
    {
        return match ($key) {
            'utm_source'     => 'UTM source',
            'utm_medium'     => 'UTM medium',
            'utm_campaign'   => 'UTM campaign',
            'utm_term'       => 'UTM term',
            'utm_content'    => 'UTM content',
            'fbclid'         => 'Facebook click ID',
            'gclid'          => 'Google click ID',
            'gbraid'         => 'Google gbraid',
            'wbraid'         => 'Google wbraid',
            'ttclid'         => 'TikTok click ID',
            'msclkid'        => 'Microsoft click ID',
            'li_fat_id'      => 'LinkedIn click ID',
            'twclid'         => 'X / Twitter click ID',
            'igshid'         => 'Instagram share ID',
            'sccid', 'ScCid' => 'Snapchat click ID',
            'epik'           => 'Pinterest click ID',
            'rdt_cid'        => 'Reddit click ID',
            '_fbp'           => 'Facebook browser ID (_fbp)',
            '_fbc'           => 'Facebook click cookie (_fbc)',
            '_ttp'           => 'TikTok cookie',
            '_gcl_aw'        => 'Google Ads cookie',
            '_gcl_gs'        => 'Google Ads cookie',
            'referrer'       => 'Referrer URL',
            'referrer_host'  => 'Referrer host',
            'source'         => 'Classified source',
            'landing'        => 'Landing path',
            default          => $key,
        };
    }

    /**
     * Pull source / landing / attr pairs from a [Marketing] note blob.
     *
     * @return array{source: ?string, landing: ?string, pairs: array<string, string>}
     */
    public static function fromEmbeddedNotes(?string $text): array
    {
        $out = ['source' => null, 'landing' => null, 'pairs' => []];
        if (! is_string($text) || $text === '') {
            return $out;
        }

        if (! preg_match('/\[Marketing\]\s*(.+)$/s', $text, $block)) {
            return $out;
        }

        $blob = trim($block[1]);
        if (preg_match('/(?:^|\s|·)source=([^\s·]+)/i', $blob, $m)) {
            $out['source'] = $m[1];
        }
        if (preg_match('/(?:^|\s|·)landing=(\/[^\s·]*)/i', $blob, $m)) {
            $out['landing'] = $m[1];
        }
        if (preg_match('/attr:\s*(.+)$/s', $blob, $m)) {
            foreach (preg_split('/\s*\|\s*/', trim($m[1])) ?: [] as $part) {
                if (! str_contains($part, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $part, 2);
                $k = trim($k);
                $v = trim($v);
                if ($k !== '' && $v !== '') {
                    $out['pairs'][$k] = $v;
                }
            }
        }

        return $out;
    }
}
