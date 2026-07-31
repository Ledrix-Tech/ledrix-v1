<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Brand;
use App\Services\LeadIntakeService;
use App\Services\LeadScriptService;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BrandConfigController extends Controller
{
    public function __construct(
        private LeadScriptService $leadScripts,
        private SubscriptionAccessService $subscriptionAccess,
        private TenantFeatureService $tenantFeatures,
    ) {}

    public function showScript(string $host)
    {
        $brand = $this->leadScripts->resolveBrandFromHost($host);
        abort_unless($brand, 404, 'Brand script not found.');

        $tenant = $brand->tenant;
        if ($tenant && (! $this->subscriptionAccess->canUseCrm($tenant) || ! $this->tenantFeatures->enabled('api_access', (int) $brand->tenant_id))) {
            return response('console.warn("Ledrix lead capture is inactive for this account.");', 200)
                ->header('Content-Type', 'application/javascript')
                ->header('Cache-Control', 'no-store');
        }

        return response($this->leadScripts->renderForBrand($brand), 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function adminDomainScripts()
    {
        $admin = auth('admin')->user();

        $brands = Brand::query()
            ->when($admin?->tenant_id, fn ($query) => $query->where('tenant_id', $admin->tenant_id))
            ->orderBy('brand_name')
            ->get();

        return view('admin.pages.domain-scripts', [
            'brands'       => $brands,
            'scriptService'=> $this->leadScripts,
        ]);
    }

    public function domainScriptStore(Request $request)
    {
        $data = $request->validate([
            'brand_id'              => 'required|exists:brands,id',
            'lead_script'           => 'nullable|string',
            'data_fields.crm_field' => 'nullable|array',
            'data_fields.site_field'=> 'nullable|array',
        ]);

        $brand = $this->authorizedBrand((int) $data['brand_id']);
        $mapping = $this->buildFieldMapping($request);

        $this->backupBrandScript($brand);

        $brand->update([
            'lead_script'   => $data['lead_script'] ?? null,
            'field_mapping' => $mapping,
        ]);

        return back()->with('success', "Script settings saved for {$brand->brand_name}.");
    }

    public function domainScriptUpdate(Request $request, Brand $brand)
    {
        $brand = $this->authorizedBrand($brand->id);

        $validated = $request->validate([
            'lead_script'           => 'nullable|string',
            'data_fields.crm_field' => 'nullable|array',
            'data_fields.site_field'=> 'nullable|array',
        ]);

        $mapping = $this->buildFieldMapping($request);

        $this->backupBrandScript($brand);

        $brand->update([
            'lead_script'   => $validated['lead_script'] ?? null,
            'field_mapping' => $mapping,
        ]);

        return back()->with('success', "Script settings updated for {$brand->brand_name}.");
    }

    public function testLeadCapture(Brand $brand, LeadIntakeService $intake)
    {
        $brand = $this->authorizedBrand($brand->id);

        try {
            $result = $intake->storeAdminTestLead($brand);

            return response()->json([
                'ok'        => true,
                'message'   => $result['duplicate']
                    ? 'Test lead already exists (duplicate idempotency key).'
                    : 'Test lead captured successfully.',
                'lead_id'   => $result['lead']->id,
                'duplicate' => $result['duplicate'],
                'email'     => $result['lead']->email,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage() ?: 'Lead capture test failed.',
            ], $e->getStatusCode());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
            ], 422);
        }
    }

    public function checkScriptStatus(Brand $brand)
    {
        $brand = $this->authorizedBrand($brand->id);

        $url = $this->leadScripts->scriptUrlForBrand($brand);
        $tenant = $brand->tenant;
        $active = $tenant
            && $this->subscriptionAccess->canUseCrm($tenant)
            && $this->tenantFeatures->enabled('api_access', (int) $brand->tenant_id);

        return response()->json([
            'ok'           => true,
            'script_url'   => $url,
            'script_active'=> (bool) $active,
            'brand_host'   => $brand->brand_host,
            'has_mapping'  => ! empty($brand->field_mapping),
            'has_override' => ! empty($brand->lead_script),
        ]);
    }

    private function authorizedBrand(int $brandId): Brand
    {
        $tenantId = TenantContext::require();

        return Brand::query()
            ->whereKey($brandId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }

    /** @return array<string, string> */
    private function buildFieldMapping(Request $request): array
    {
        $mapping = [];
        $crmFields = $request->input('data_fields.crm_field', []);
        $siteFields = $request->input('data_fields.site_field', []);

        foreach ($crmFields as $i => $crmKey) {
            $crmKey = trim((string) $crmKey);
            $siteKey = trim((string) ($siteFields[$i] ?? ''));
            if ($crmKey && $siteKey) {
                $mapping[$crmKey] = $siteKey;
            }
        }

        return $mapping ?: LeadScriptService::defaultFieldMapping();
    }

    private function backupBrandScript(Brand $brand): void
    {
        if (! $brand->lead_script && ! $brand->field_mapping) {
            return;
        }

        Storage::disk('local')->put(
            "backups/brand_scripts/{$brand->id}_" . now()->format('Ymd_His') . '.json',
            json_encode([
                'lead_script'   => $brand->lead_script,
                'field_mapping' => $brand->field_mapping,
            ], JSON_PRETTY_PRINT)
        );
    }
}
