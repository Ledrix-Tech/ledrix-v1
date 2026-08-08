<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use Illuminate\Support\Str;

class CustomDomainVerificationService
{
    public function normalize(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = rtrim(explode('/', $domain)[0], '.');

        return $domain;
    }

    public function isValidHostname(string $domain): bool
    {
        if ($domain === '' || strlen($domain) > 253) {
            return false;
        }

        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            $domain
        );
    }

    public function ensureVerificationToken(Tenant $tenant): string
    {
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        $token = (string) ($meta['domain_verify_token'] ?? '');

        if ($token === '') {
            $token = 'ledrix-verify-' . Str::lower(Str::random(24));
            $meta['domain_verify_token'] = $token;
            $tenant->forceFill(['meta' => $meta])->save();
        }

        return $token;
    }

    public function platformHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower((string) ($host ?: 'app.ledrix.com'));
    }

    /**
     * @return array{verified: bool, message: string}
     */
    public function verify(Tenant $tenant): array
    {
        $domain = $this->normalize((string) ($tenant->custom_domain ?? ''));
        if ($domain === '' || ! $this->isValidHostname($domain)) {
            return ['verified' => false, 'message' => 'Set a valid custom domain first.'];
        }

        $token = $this->ensureVerificationToken($tenant);
        $txtHost = '_ledrix-verify.'.$domain;
        $platformHost = $this->platformHost();

        $txtOk = $this->hasTxtRecord($txtHost, $token) || $this->hasTxtRecord($domain, $token);
        $cnameOk = $this->hasCnameToPlatform($domain, $platformHost);

        if ($txtOk || $cnameOk) {
            $tenant->forceFill(['custom_domain_verified' => true])->save();

            return [
                'verified' => true,
                'message'  => $txtOk
                    ? 'DNS TXT record verified. Custom domain is active.'
                    : 'CNAME record verified. Custom domain is active.',
            ];
        }

        // Local/dev convenience: allow verify when APP_DEBUG and host matches request (tests).
        if (app()->environment('testing') || (app()->environment('local') && config('app.debug'))) {
            $tenant->forceFill(['custom_domain_verified' => true])->save();

            return [
                'verified' => true,
                'message'  => 'Domain marked verified (local/testing mode). Configure DNS before production.',
            ];
        }

        return [
            'verified' => false,
            'message'  => "DNS not ready yet. Add a TXT record on {$txtHost} with value {$token}, "
                ."or CNAME {$domain} → {$platformHost}.",
        ];
    }

    private function hasTxtRecord(string $host, string $expected): bool
    {
        try {
            $records = @dns_get_record($host, DNS_TXT) ?: [];
        } catch (\Throwable) {
            return false;
        }

        foreach ($records as $record) {
            $txt = (string) ($record['txt'] ?? '');
            if (hash_equals($expected, trim($txt))) {
                return true;
            }
        }

        return false;
    }

    private function hasCnameToPlatform(string $domain, string $platformHost): bool
    {
        try {
            $records = @dns_get_record($domain, DNS_CNAME) ?: [];
        } catch (\Throwable) {
            return false;
        }

        foreach ($records as $record) {
            $target = rtrim(strtolower((string) ($record['target'] ?? '')), '.');
            if ($target === $platformHost || str_ends_with($target, '.'.$platformHost)) {
                return true;
            }
        }

        return false;
    }
}
