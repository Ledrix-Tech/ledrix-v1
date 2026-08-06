<?php

namespace App\Services\Security;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Minimal RFC 6238 TOTP (SHA1, 30s, 6 digits) — no external package.
 */
class TotpService
{
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->at($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(string $secret, string $email, string $issuer = 'Ledrix Super Admin'): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerParam = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerParam}&digits=6&period=30";
    }

    /** @return list<string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }

        return $codes;
    }

    /** @param  list<string>  $plainCodes */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $code) => hash('sha256', strtoupper($code)), $plainCodes);
    }

    /** @param  list<string>  $hashedCodes */
    public function consumeRecoveryCode(array $hashedCodes, string $plain): ?array
    {
        $hash = hash('sha256', strtoupper(trim($plain)));
        $idx = array_search($hash, $hashedCodes, true);
        if ($idx === false) {
            return null;
        }

        unset($hashedCodes[$idx]);

        return array_values($hashedCodes);
    }

    private function at(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $secret .= $alphabet[bindec($chunk)];
        }

        return $secret;
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        if ($secret === '') {
            throw new RuntimeException('Invalid TOTP secret.');
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $val = strpos($alphabet, $char);
            if ($val === false) {
                throw new RuntimeException('Invalid TOTP secret.');
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $data = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $data .= chr(bindec($chunk));
            }
        }

        return $data;
    }
}
