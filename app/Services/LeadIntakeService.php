<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Central\Tenant;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Seller;
use App\Notifications\LeadAutoReplyNotification;
use App\Notifications\LeadCreatedFsNotification;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantLimitService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LeadIntakeService
{
    private const CORE_FIELDS = ['name', 'email', 'phone', 'service', 'message'];

    public function __construct(
        private LeadAssigner $assigner,
        private LeadClassifier $classifier,
        private TenantFeatureService $tenantFeatures,
        private TenantLimitService $limits,
        private SubscriptionAccessService $subscriptionAccess,
    ) {}

    /**
     * Full CRM lead intake: resolve brand → classify → tenant-scoped client → assign seller → store.
     *
     * @return array{
     *     duplicate: bool,
     *     lead: Lead,
     *     client: Client,
     *     seller: Seller,
     *     meta: array,
     *     prediction: ?array
     * }
     */
    public function storeFromCrmPost(Request $req, bool $skipOriginCheck = false): array
    {
        $incoming = $req->all();

        $brand = $this->resolveBrand($incoming['url'] ?? null, $req);
        abort_unless($brand, 422, 'Unknown brand.');
        abort_unless($brand->tenant_id, 422, 'Brand tenant is not configured.');

        $this->assertIntakeAllowed($brand, $req, $skipOriginCheck);

        $incoming = $this->applyFieldMapping($incoming, $brand);

        $validated = $this->validateCoreFields($incoming);
        $meta = $this->buildMeta($incoming, $validated, $req);
        $prediction = $this->classifyLead($validated, (int) $brand->tenant_id);

        $idem = $req->header('Idempotency-Key');
        if ($idem) {
            $meta['idem'] = $idem;
        }

        TenantContext::set((int) $brand->tenant_id);

        $this->limits->assertCanCreateLead((int) $brand->tenant_id);

        try {
            if ($idem) {
                $existing = Lead::query()
                    ->where('brand_id', $brand->id)
                    ->where('meta->idem', $idem)
                    ->first();

                if ($existing) {
                    return [
                        'duplicate'  => true,
                        'lead'       => $existing,
                        'client'     => $existing->client,
                        'seller'     => $existing->seller,
                        'meta'       => $meta,
                        'prediction' => $this->decodePrediction($existing->prediction),
                    ];
                }
            }

            [$lead, $client, $seller] = $this->persistLead(
                brand: $brand,
                validated: $validated,
                meta: $meta,
                prediction: $prediction,
            );

            return [
                'duplicate'  => false,
                'lead'       => $lead,
                'client'     => $client,
                'seller'     => $seller,
                'meta'       => $meta,
                'prediction' => $prediction,
            ];
        } finally {
            TenantContext::clear();
        }
    }

    /**
     * Send a test lead from admin Domain Scripts (skips origin check, no customer emails).
     *
     * @return array{duplicate: bool, lead: Lead, client: Client, seller: Seller, meta: array, prediction: ?array}
     */
    public function storeAdminTestLead(Brand $brand): array
    {
        abort_unless($brand->tenant_id, 422, 'Brand tenant is not configured.');

        $host = $brand->brand_host ?: 'example.com';
        $stamp = now()->format('Y-m-d H:i:s');

        $payload = [
            'name'       => "Test Lead ({$stamp})",
            'email'      => 'test+' . $brand->id . '.' . time() . '@ledrix.test',
            'phone'      => '0000000000',
            'service'    => 'Domain script test',
            'message'    => 'Automated test from Admin → Domain Scripts.',
            'brand_key'  => $brand->public_form_token,
            'url'        => 'https://' . $host . '/',
            'brand_host' => $host,
            'channel'    => 'admin_test',
            'is_test'    => true,
        ];

        $request = Request::create(route('crm.leads.post'), 'POST', $payload, [], [], [
            'HTTP_ACCEPT'          => 'application/json',
            'HTTP_IDEMPOTENCY-KEY' => 'admin-test-' . $brand->id . '-' . time(),
        ]);

        return $this->storeFromCrmPost($request, skipOriginCheck: true);
    }

    public function resolveBrand(?string $url, Request $req): ?Brand
    {
        $brandKey = trim((string) $req->input('brand_key', ''));

        $brandFromKey = null;
        if ($brandKey !== '') {
            $brandFromKey = Brand::withoutGlobalScopes()
                ->where('public_form_token', $brandKey)
                ->first();
        }

        $brandFromHost = $this->resolveBrandFromHostUrl($url)
            ?? $this->resolveBrandFromRequestOrigin($req);

        if ($brandFromKey && $brandFromHost) {
            return $brandFromKey->id === $brandFromHost->id ? $brandFromKey : null;
        }

        return $brandFromKey ?? $brandFromHost;
    }

    public function resolveBrandFromHostUrl(?string $url): ?Brand
    {
        return $this->brandFromHost(self::normalizeHost($url));
    }

    public function resolveBrandFromRequestOrigin(Request $req): ?Brand
    {
        $origin = $req->headers->get('Origin') ?: $req->headers->get('Referer');

        return $this->brandFromHost(self::normalizeHost($origin));
    }

    private function brandFromHost(?string $host): ?Brand
    {
        if (! $host) {
            return null;
        }

        return Brand::withoutGlobalScopes()
            ->where(function ($query) use ($host) {
                $query->where('brand_host', $host)
                    ->orWhereJsonContains('allowed_origins', $host)
                    ->orWhereJsonContains('allowed_origins', 'www.' . $host);
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function applyFieldMapping(array $incoming, Brand $brand): array
    {
        $mapping = app(LeadScriptService::class)->fieldMappingForBrand($brand);
        $normalized = $incoming;

        foreach ($mapping as $crmField => $siteField) {
            $siteField = trim((string) $siteField);
            if ($siteField === '' || array_key_exists($crmField, $normalized)) {
                continue;
            }

            if (array_key_exists($siteField, $incoming)) {
                $normalized[$crmField] = $incoming[$siteField];
            }
        }

        return $normalized;
    }

    public function assertPublicIntakeAllowed(Brand $brand, Request $req, bool $skipOriginCheck = false): void
    {
        $this->assertIntakeAllowed($brand, $req, $skipOriginCheck);
    }

    private function assertIntakeAllowed(Brand $brand, Request $req, bool $skipOriginCheck = false): void
    {
        $brandKey = trim((string) $req->input('brand_key', ''));
        abort_unless($brandKey !== '', 422, 'Brand key is required.');
        abort_unless(
            hash_equals((string) $brand->public_form_token, $brandKey),
            403,
            'Invalid brand key.'
        );

        $tenant = Tenant::query()->find((int) $brand->tenant_id);
        abort_unless($tenant, 422, 'Tenant not found.');

        abort_unless(
            $this->subscriptionAccess->canUseCrm($tenant),
            403,
            'Lead capture is unavailable. Please renew or activate your subscription.'
        );

        $this->tenantFeatures->assertEnabled(
            'api_access',
            (int) $brand->tenant_id,
            'API lead capture is not included in your subscription plan.'
        );

        if ($brand->status && ! in_array(strtolower((string) $brand->status), ['active', ''], true)) {
            abort(403, 'This brand is not active for lead capture.');
        }

        if (! $skipOriginCheck) {
            $this->assertOriginAllowed($brand, $req);
        }
    }

    private function assertOriginAllowed(Brand $brand, Request $req): void
    {
        $originHeader = $req->headers->get('Origin') ?: $req->headers->get('Referer');
        if (! $originHeader) {
            return;
        }

        $originHost = self::normalizeHost($originHeader);
        if (! $originHost) {
            return;
        }

        $allowed = collect(array_merge(
            array_filter([$brand->brand_host]),
            (array) $brand->allowed_origins
        ))
            ->map(fn ($host) => self::normalizeHost((string) $host))
            ->filter()
            ->unique()
            ->values();

        abort_unless(
            $allowed->contains($originHost),
            403,
            'Origin is not allowed for this brand.'
        );
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function validateCoreFields(array $incoming): array
    {
        $core = [];

        foreach (self::CORE_FIELDS as $field) {
            $core[$field] = isset($incoming[$field]) ? trim((string) $incoming[$field]) : null;
        }

        return validator($core, [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'required|string|max:30',
            'message' => 'nullable|string|max:4000',
            'service' => 'nullable|string|max:255',
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildMeta(array $incoming, array $validated, Request $req): array
    {
        $meta = $incoming;

        foreach (self::CORE_FIELDS as $field) {
            unset($meta[$field]);
        }

        unset($meta['brand_key'], $meta['api_key']);

        $meta['ip']       = $req->ip();
        $meta['ua']       = substr((string) $req->userAgent(), 0, 255);
        $meta['url']      = $incoming['url'] ?? $req->headers->get('Referer');
        $meta['timezone'] = $incoming['timezone'] ?? config('app.timezone');
        $meta['service']  = $validated['service'] ?? null;

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|null
     */
    private function classifyLead(array $validated, int $tenantId): ?array
    {
        if (! $this->tenantFeatures->hasLeadPrediction($tenantId)) {
            return null;
        }

        try {
            return $this->classifier->classify($validated);
        } catch (\Throwable $e) {
            Log::warning('Lead classification failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>|null  $prediction
     * @return array{0: Lead, 1: Client, 2: Seller}
     */
    private function persistLead(Brand $brand, array $validated, array $meta, ?array $prediction): array
    {
        $tenantId = (int) $brand->tenant_id;

        return DB::transaction(function () use ($brand, $validated, $meta, $prediction, $tenantId) {
            $email = strtolower(trim($validated['email']));

            $client = Client::firstOrCreate(
                ['email' => $email],
                [
                    'tenant_id' => $tenantId,
                    'name'      => $validated['name'],
                    'phone'     => $validated['phone'] ?? null,
                ]
            );

            if (! $client->tenant_id) {
                $client->forceFill(['tenant_id' => $tenantId])->save();
            }

            $seller = $this->assigner->assignNext($brand);

            $lead = Lead::create([
                'tenant_id'  => $tenantId,
                'brand_id'   => $brand->id,
                'seller_id'  => $seller->id,
                'client_id'  => $client->id,
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'phone'      => $validated['phone'],
                'service'    => $validated['service'] ?? null,
                'message'    => $validated['message'] ?? null,
                'status'     => 'new',
                'prediction' => $prediction,
                'domain_url' => self::normalizeHost($meta['url'] ?? null),
                'meta'       => $meta,
            ]);

            DB::afterCommit(function () use ($lead, $seller, $meta) {
                if (! empty($meta['is_test'])) {
                    return;
                }

                try {
                    Notification::route('mail', $lead->email)
                        ->notify(new LeadAutoReplyNotification($lead));

                    if ($seller->email) {
                        Notification::route('mail', $seller->email)
                            ->notify(new LeadCreatedFsNotification($lead, $seller));
                    }
                } catch (\Throwable $e) {
                    Log::error('Lead notifications failed', [
                        'lead_id' => $lead->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            });

            return [$lead, $client, $seller];
        });
    }

    public static function normalizeHost(?string $url): ?string
    {
        return Brand::normalizeHost($url);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePrediction(mixed $prediction): ?array
    {
        if (is_array($prediction)) {
            return $prediction;
        }

        if (is_string($prediction)) {
            $decoded = json_decode($prediction, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
