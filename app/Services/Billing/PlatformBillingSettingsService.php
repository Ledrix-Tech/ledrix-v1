<?php

namespace App\Services\Billing;

use App\Models\Central\PlatformBillingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PlatformBillingSettingsService
{
    public const PROVIDERS = ['stripe', 'payfast', 'meezan', 'jazzcash', 'payoneer'];

    private const CACHE_KEY = 'platform_billing_settings.v2';

    /** Sensitive credential keys — blank form values keep existing secrets. */
    public const SENSITIVE_KEYS = [
        'secret',
        'webhook_secret',
        'secured_key',
        'password',
        'integrity_salt',
    ];

    /**
     * Field definitions shown / accepted in super-admin UI.
     *
     * @return array<string, array{label: string, fields: array<string, array{label: string, type?: string, required?: bool, sensitive?: bool, placeholder?: string}>}>
     */
    public function definitions(): array
    {
        return [
            'stripe' => [
                'label'  => 'Stripe',
                'help'   => 'Card payments for tenant subscriptions. Enable to show Stripe checkout on the billing page.',
                'fields' => [
                    'key'            => ['label' => 'Publishable key', 'type' => 'text', 'required' => true],
                    'secret'         => ['label' => 'Secret key', 'type' => 'password', 'required' => true, 'sensitive' => true],
                    'webhook_secret' => ['label' => 'Webhook secret', 'type' => 'password', 'required' => false, 'sensitive' => true],
                ],
            ],
            'payfast' => [
                'label'  => 'PayFast',
                'help'   => 'PayFast Pakistan hosted checkout (PKR). Enable to show PayFast on the billing page.',
                'fields' => [
                    'mode'          => ['label' => 'Mode (sandbox|live)', 'type' => 'text', 'required' => true, 'placeholder' => 'sandbox'],
                    'merchant_id'   => ['label' => 'Merchant ID', 'type' => 'text', 'required' => true],
                    'merchant_name' => ['label' => 'Merchant name', 'type' => 'text', 'required' => false],
                    'secured_key'   => ['label' => 'Secured key', 'type' => 'password', 'required' => true, 'sensitive' => true],
                    'grant_type'    => ['label' => 'Grant type', 'type' => 'text', 'required' => false, 'placeholder' => 'client_credentials'],
                    'token_url'     => ['label' => 'Token URL', 'type' => 'url', 'required' => false],
                    'checkout_url'  => ['label' => 'Checkout URL', 'type' => 'url', 'required' => true],
                    'return_url'    => ['label' => 'Return URL', 'type' => 'url', 'required' => false],
                ],
            ],
            'meezan' => [
                'label'  => 'Meezan Bank Transfer',
                'help'   => 'Manual bank transfer + Raast QR. Enable to show Meezan pay-now on the billing page. Super-admin confirms pending payments.',
                'fields' => [
                    'bank_name'      => ['label' => 'Bank name', 'type' => 'text', 'required' => true, 'placeholder' => 'Meezan Bank'],
                    'account_title'  => ['label' => 'Account title', 'type' => 'text', 'required' => true],
                    'account_number' => ['label' => 'Account number', 'type' => 'text', 'required' => true],
                    'iban'           => ['label' => 'IBAN (for Raast QR)', 'type' => 'text', 'required' => false],
                    'branch'         => ['label' => 'Branch', 'type' => 'text', 'required' => false],
                    'merchant_city'  => ['label' => 'Merchant city', 'type' => 'text', 'required' => false, 'placeholder' => 'Karachi'],
                ],
            ],
            'jazzcash' => [
                'label'  => 'JazzCash',
                'help'   => 'JazzCash merchant checkout for PKR subscriptions. Credentials from JazzCash Merchant Dashboard.',
                'fields' => [
                    'sandbox'        => ['label' => 'Sandbox (1|0)', 'type' => 'text', 'required' => false, 'placeholder' => '1'],
                    'merchant_id'    => ['label' => 'Merchant ID', 'type' => 'text', 'required' => true],
                    'password'       => ['label' => 'Password', 'type' => 'password', 'required' => true, 'sensitive' => true],
                    'integrity_salt' => ['label' => 'Integrity salt', 'type' => 'password', 'required' => true, 'sensitive' => true],
                    'return_url'     => ['label' => 'Return URL', 'type' => 'url', 'required' => false],
                    'usd_to_pkr_rate'=> ['label' => 'USD→PKR rate', 'type' => 'text', 'required' => false, 'placeholder' => '280'],
                ],
            ],
            'payoneer' => [
                'label'  => 'Payoneer',
                'help'   => 'Manual Payoneer invoices. Tenants pay your Payoneer email; Super Admin confirms in Subscription Payments.',
                'fields' => [
                    'receiver_email' => ['label' => 'Receiver email', 'type' => 'email', 'required' => true],
                    'receiver_name'  => ['label' => 'Receiver name', 'type' => 'text', 'required' => false],
                    'currency'       => ['label' => 'Currency', 'type' => 'text', 'required' => false, 'placeholder' => 'USD'],
                ],
            ],
        ];
    }

    public function tableReady(): bool
    {
        try {
            return Schema::connection('central')->hasTable('platform_billing_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Ensure rows exist (seeded from .env on first use). */
    public function ensureSeeded(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        $created = false;

        foreach (self::PROVIDERS as $provider) {
            if (PlatformBillingSetting::query()->where('provider', $provider)->exists()) {
                continue;
            }

            PlatformBillingSetting::query()->create([
                'provider'    => $provider,
                'enabled'     => $this->envLooksConfigured($provider),
                'credentials' => $this->credentialsFromEnv($provider),
            ]);
            $created = true;
        }

        if ($created) {
            $this->forgetCache();
        }
    }

    /** @return array<string, array{enabled: bool, credentials: array<string, mixed>, configured: bool, ready: bool}> */
    public function allForAdmin(): array
    {
        $this->ensureSeeded();

        $out = [];
        foreach (self::PROVIDERS as $provider) {
            $row = $this->find($provider);
            $credentials = $row?->credentials_array ?? $this->credentialsFromEnv($provider);
            $enabled = (bool) ($row?->enabled ?? false);

            $out[$provider] = [
                'enabled'     => $enabled,
                'credentials' => $credentials,
                'configured'  => $this->hasRequiredCredentials($provider, $credentials),
                'ready'       => $enabled && $this->hasRequiredCredentials($provider, $credentials),
                'masked'      => $this->maskSensitive($credentials),
            ];
        }

        return $out;
    }

    public function isEnabled(string $provider): bool
    {
        $snapshot = $this->snapshot();

        return (bool) ($snapshot[$provider]['enabled'] ?? false);
    }

    public function isReady(string $provider): bool
    {
        $snapshot = $this->snapshot();

        return (bool) ($snapshot[$provider]['ready'] ?? false);
    }

    /**
     * Merge DB credentials into runtime config so existing billing services keep working.
     */
    public function applyToConfig(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        try {
            $snapshot = $this->snapshot();
        } catch (\Throwable $e) {
            Log::debug('Platform billing settings not applied', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($snapshot as $provider => $data) {
            $credentials = $data['credentials'] ?? [];
            if ($credentials === []) {
                continue;
            }

            match ($provider) {
                'stripe' => config([
                    'services.stripe.key'            => $credentials['key'] ?? config('services.stripe.key'),
                    'services.stripe.secret'         => $credentials['secret'] ?? config('services.stripe.secret'),
                    'services.stripe.webhook_secret' => $credentials['webhook_secret'] ?? config('services.stripe.webhook_secret'),
                ]),
                'payfast' => config([
                    'services.payfast.mode'          => $credentials['mode'] ?? config('services.payfast.mode'),
                    'services.payfast.merchant_id'   => $credentials['merchant_id'] ?? config('services.payfast.merchant_id'),
                    'services.payfast.merchant_name' => $credentials['merchant_name'] ?? config('services.payfast.merchant_name'),
                    'services.payfast.secured_key'   => $credentials['secured_key'] ?? config('services.payfast.secured_key'),
                    'services.payfast.grant_type'    => $credentials['grant_type'] ?? config('services.payfast.grant_type'),
                    'services.payfast.token_url'     => $credentials['token_url'] ?? config('services.payfast.token_url'),
                    'services.payfast.checkout_url'  => $credentials['checkout_url'] ?? config('services.payfast.checkout_url'),
                    'services.payfast.return_url'    => $credentials['return_url'] ?? config('services.payfast.return_url'),
                ]),
                'meezan' => config([
                    'services.bank_transfer.pkr.bank_name'      => $credentials['bank_name'] ?? config('services.bank_transfer.pkr.bank_name'),
                    'services.bank_transfer.pkr.account_title'  => $credentials['account_title'] ?? config('services.bank_transfer.pkr.account_title'),
                    'services.bank_transfer.pkr.account_number' => $credentials['account_number'] ?? config('services.bank_transfer.pkr.account_number'),
                    'services.bank_transfer.pkr.iban'           => $credentials['iban'] ?? config('services.bank_transfer.pkr.iban'),
                    'services.bank_transfer.pkr.branch'         => $credentials['branch'] ?? config('services.bank_transfer.pkr.branch'),
                    'services.bank_transfer.pkr.merchant_city'  => $credentials['merchant_city'] ?? config('services.bank_transfer.pkr.merchant_city'),
                ]),
                'jazzcash' => config([
                    'services.jazzcash.sandbox'         => filter_var($credentials['sandbox'] ?? config('services.jazzcash.sandbox'), FILTER_VALIDATE_BOOLEAN),
                    'services.jazzcash.merchant_id'     => $credentials['merchant_id'] ?? config('services.jazzcash.merchant_id'),
                    'services.jazzcash.password'        => $credentials['password'] ?? config('services.jazzcash.password'),
                    'services.jazzcash.integrity_salt'  => $credentials['integrity_salt'] ?? config('services.jazzcash.integrity_salt'),
                    'services.jazzcash.return_url'      => $credentials['return_url'] ?? config('services.jazzcash.return_url'),
                    'services.jazzcash.usd_to_pkr_rate' => (float) ($credentials['usd_to_pkr_rate'] ?? config('services.jazzcash.usd_to_pkr_rate')),
                ]),
                'payoneer' => config([
                    'services.payoneer.receiver_email' => $credentials['receiver_email'] ?? config('services.payoneer.receiver_email'),
                    'services.payoneer.receiver_name'  => $credentials['receiver_name'] ?? config('services.payoneer.receiver_name'),
                    'services.payoneer.currency'       => $credentials['currency'] ?? config('services.payoneer.currency'),
                ]),
                default => null,
            };
        }

        config([
            'services.platform_billing' => collect($snapshot)
                ->mapWithKeys(fn ($data, $provider) => [$provider => [
                    'enabled' => (bool) ($data['enabled'] ?? false),
                    'ready'   => (bool) ($data['ready'] ?? false),
                ]])
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function update(string $provider, bool $enabled, array $credentials, ?int $updatedBy = null): PlatformBillingSetting
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(422, 'Unknown billing provider.');
        }

        $this->ensureSeeded();

        $row = PlatformBillingSetting::query()->firstOrNew(['provider' => $provider]);
        $existing = $row->exists ? $row->credentials_array : $this->credentialsFromEnv($provider);

        $merged = $this->mergeCredentials($provider, $existing, $credentials);

        $row->enabled = $enabled;
        $row->credentials = $merged;
        $row->updated_by = $updatedBy;
        $row->save();

        $this->forgetCache();
        $this->applyToConfig();

        return $row->fresh();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array{enabled: bool, credentials: array<string, mixed>, ready: bool}> */
    private function snapshot(): array
    {
        if (! $this->tableReady()) {
            return $this->envSnapshot();
        }

        $this->ensureSeeded();

        return Cache::remember(self::CACHE_KEY, 300, function () {
            $rows = PlatformBillingSetting::query()
                ->whereIn('provider', self::PROVIDERS)
                ->get()
                ->keyBy('provider');

            $out = [];
            foreach (self::PROVIDERS as $provider) {
                $row = $rows->get($provider);
                $credentials = $row?->credentials_array ?: $this->credentialsFromEnv($provider);
                $enabled = (bool) ($row?->enabled ?? false);

                $out[$provider] = [
                    'enabled'     => $enabled,
                    'credentials' => $credentials,
                    'ready'       => $enabled && $this->hasRequiredCredentials($provider, $credentials),
                ];
            }

            return $out;
        });
    }

    /** @return array<string, array{enabled: bool, credentials: array<string, mixed>, ready: bool}> */
    private function envSnapshot(): array
    {
        $out = [];
        foreach (self::PROVIDERS as $provider) {
            $credentials = $this->credentialsFromEnv($provider);
            $configured = $this->hasRequiredCredentials($provider, $credentials);
            $out[$provider] = [
                'enabled'     => $configured,
                'credentials' => $credentials,
                'ready'       => $configured,
            ];
        }

        return $out;
    }

    private function find(string $provider): ?PlatformBillingSetting
    {
        return PlatformBillingSetting::query()->where('provider', $provider)->first();
    }

    /** @return array<string, mixed> */
    private function credentialsFromEnv(string $provider): array
    {
        return match ($provider) {
            'stripe' => [
                'key'            => config('services.stripe.key'),
                'secret'         => config('services.stripe.secret'),
                'webhook_secret' => config('services.stripe.webhook_secret'),
            ],
            'payfast' => [
                'mode'          => config('services.payfast.mode', 'sandbox'),
                'merchant_id'   => config('services.payfast.merchant_id'),
                'merchant_name' => config('services.payfast.merchant_name'),
                'secured_key'   => config('services.payfast.secured_key'),
                'grant_type'    => config('services.payfast.grant_type', 'client_credentials'),
                'token_url'     => config('services.payfast.token_url'),
                'checkout_url'  => config('services.payfast.checkout_url'),
                'return_url'    => config('services.payfast.return_url'),
            ],
            'meezan' => [
                'bank_name'      => config('services.bank_transfer.pkr.bank_name'),
                'account_title'  => config('services.bank_transfer.pkr.account_title'),
                'account_number' => config('services.bank_transfer.pkr.account_number'),
                'iban'           => config('services.bank_transfer.pkr.iban'),
                'branch'         => config('services.bank_transfer.pkr.branch'),
                'merchant_city'  => config('services.bank_transfer.pkr.merchant_city', 'Karachi'),
            ],
            'jazzcash' => [
                'sandbox'         => config('services.jazzcash.sandbox') ? '1' : '0',
                'merchant_id'     => config('services.jazzcash.merchant_id'),
                'password'        => config('services.jazzcash.password'),
                'integrity_salt'  => config('services.jazzcash.integrity_salt'),
                'return_url'      => config('services.jazzcash.return_url'),
                'usd_to_pkr_rate' => config('services.jazzcash.usd_to_pkr_rate'),
            ],
            'payoneer' => [
                'receiver_email' => config('services.payoneer.receiver_email'),
                'receiver_name'  => config('services.payoneer.receiver_name'),
                'currency'       => config('services.payoneer.currency', 'USD'),
            ],
            default => [],
        };
    }

    private function envLooksConfigured(string $provider): bool
    {
        return $this->hasRequiredCredentials($provider, $this->credentialsFromEnv($provider));
    }

    /** @param  array<string, mixed>  $credentials */
    private function hasRequiredCredentials(string $provider, array $credentials): bool
    {
        $defs = $this->definitions()[$provider]['fields'] ?? [];

        foreach ($defs as $key => $meta) {
            if (! ($meta['required'] ?? false)) {
                continue;
            }

            $value = trim((string) ($credentials[$key] ?? ''));
            if ($value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeCredentials(string $provider, array $existing, array $incoming): array
    {
        $allowed = array_keys($this->definitions()[$provider]['fields'] ?? []);
        $merged = $existing;

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $incoming)) {
                continue;
            }

            $value = is_string($incoming[$key]) ? trim($incoming[$key]) : $incoming[$key];

            if (in_array($key, self::SENSITIVE_KEYS, true) && ($value === null || $value === '')) {
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    private function maskSensitive(array $credentials): array
    {
        $masked = $credentials;

        foreach (self::SENSITIVE_KEYS as $key) {
            if (! empty($masked[$key])) {
                $masked[$key . '_set'] = true;
                $masked[$key] = '';
            } else {
                $masked[$key . '_set'] = false;
            }
        }

        return $masked;
    }
}
