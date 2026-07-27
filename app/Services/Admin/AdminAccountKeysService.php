<?php

namespace App\Services\Admin;

use App\Models\AccountKey;
use App\Models\Brand;
use App\Support\AccountKeySecrets;
use App\Support\TenantContext;
use App\Services\Tenant\TenantLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminAccountKeysService
{
    public function __construct(
        private TenantLimitService $limits,
    ) {}

    /**
     * @return array{keys: Collection<int, AccountKey>, brands: Collection<int, Brand>}
     */
    public function pageData(): array
    {
        return [
            'keys'   => AccountKey::with('brand')
                ->whereNotNull('brand_id')
                ->orderBy('brand_id')
                ->orderBy('module')
                ->get(),
            'brands' => Brand::query()
                ->orderBy('brand_name')
                ->get(['id', 'brand_name', 'brand_url']),
        ];
    }

    public function store(Request $request): AccountKey
    {
        $validated = $this->validatePayload($request, requireBrand: true);

        $brand = Brand::findOrFail($validated['brand_id']);
        $isNew = ! AccountKey::query()
            ->where('brand_id', $brand->id)
            ->where('module', $validated['module'])
            ->exists();

        if ($isNew) {
            $this->limits->assertCanCreateAccountKey($brand->tenant_id);
        }

        $payload = AccountKeySecrets::omitBlankSecrets([
            'module'                 => $validated['module'],
            'brand_id'               => $brand->id,
            'brand_url'              => $brand->brand_url,
            'tenant_id'              => $brand->tenant_id ?? TenantContext::resolve(),
            'stripe_publishable_key' => $validated['stripe_publishable_key'] ?? null,
            'stripe_secret_key'      => $validated['stripe_secret_key'] ?? null,
            'stripe_webhook_secret'  => $validated['stripe_webhook_secret'] ?? null,
            'paypal_client_id'       => $validated['paypal_client_id'] ?? null,
            'paypal_secret'          => $validated['paypal_secret'] ?? null,
            'paypal_webhook_id'      => $validated['paypal_webhook_id'] ?? null,
            'paypal_base_url'        => $validated['paypal_base_url'] ?? null,
            'status'                 => 'active',
        ]);

        $key = AccountKey::updateOrCreate(
            ['brand_id' => $brand->id, 'module' => $validated['module']],
            $payload
        );

        $this->logChange($key, $isNew ? 'created' : 'updated', array_keys($payload));

        return $key;
    }

    public function update(Request $request, int $id): AccountKey
    {
        $validated = $this->validatePayload($request, requireBrand: false);

        $key = AccountKey::findOrFail($id);

        $payload = AccountKeySecrets::omitBlankSecrets([
            'module'                 => $validated['module'],
            'stripe_publishable_key' => $validated['stripe_publishable_key'] ?? null,
            'stripe_secret_key'      => $validated['stripe_secret_key'] ?? null,
            'stripe_webhook_secret'  => $validated['stripe_webhook_secret'] ?? null,
            'paypal_client_id'       => $validated['paypal_client_id'] ?? null,
            'paypal_secret'          => $validated['paypal_secret'] ?? null,
            'paypal_webhook_id'      => $validated['paypal_webhook_id'] ?? null,
            'paypal_base_url'        => $validated['paypal_base_url'] ?? null,
            'status'                 => $validated['status'],
        ]);

        if (! $key->tenant_id && $key->brand_id) {
            $payload['tenant_id'] = Brand::whereKey($key->brand_id)->value('tenant_id')
                ?? TenantContext::resolve();
        }

        $key->update($payload);

        $this->logChange($key, 'updated', array_keys($payload));

        return $key->fresh(['brand']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $requireBrand): array
    {
        $rules = [
            'module'                 => 'required|in:ppc,upwork',
            'stripe_secret_key'      => 'nullable|string|max:500',
            'stripe_publishable_key' => 'nullable|string|max:500',
            'stripe_webhook_secret'  => 'nullable|string|max:500',
            'paypal_client_id'       => 'nullable|string|max:500',
            'paypal_secret'          => 'nullable|string|max:500',
            'paypal_webhook_id'      => 'nullable|string|max:255',
            'paypal_base_url'        => 'nullable|url|max:255',
        ];

        if ($requireBrand) {
            $rules['brand_id'] = 'required|exists:brands,id';
        } else {
            $rules['status'] = 'required|in:active,inactive';
        }

        return $request->validate($rules);
    }

    /**
     * @param  list<string>  $fields
     */
    private function logChange(AccountKey $key, string $action, array $fields): void
    {
        $loggedFields = array_values(array_diff($fields, AccountKeySecrets::SENSITIVE_FIELDS));

        if (array_intersect($fields, AccountKeySecrets::SENSITIVE_FIELDS) !== []) {
            $loggedFields[] = 'secrets';
        }

        Log::info("Account keys {$action}", [
            'account_key_id' => $key->id,
            'brand_id'       => $key->brand_id,
            'module'         => $key->module,
            'admin_id'       => auth('admin')->id(),
            'fields'         => $loggedFields,
        ]);
    }
}
