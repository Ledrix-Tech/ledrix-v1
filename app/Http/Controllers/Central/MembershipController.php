<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Central\SuperAdminTenantFeatureService;
use App\Services\Central\SuperAdminTenantLimitService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class MembershipController extends Controller
{
    public function superCompanyProfiles()
    {
        $companies = Tenant::with(['plan:id,name,slug', 'activeMembership'])
            ->latest()
            ->paginate(20);

        return view('central.pages.company-profiles', compact('companies'));
    }

    public function superTenantShow(
        $id,
        TenantFeatureService $features,
        SuperAdminTenantFeatureService $featureOverrides,
        SuperAdminTenantLimitService $limitOverrides,
        TenantUsageService $usageService,
    ) {
        $tenant = Tenant::with([
            'plan',
            'featureOverride',
            'limitOverride',
            'activeMembership.plan',
            'memberships' => fn ($q) => $q->latest()->take(5),
            'payments'    => fn ($q) => $q->latest()->take(8),
        ])->findOrFail($id);

        $pendingPayments = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenant->id)
            ->whereIn('gateway', ['payoneer', 'bank_transfer'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->get();

        $limitSummary = collect($limitOverrides->matrixForTenant($tenant))->map(function (array $limit) {
            $effective = (int) ($limit['effective'] ?? 0);
            $used = (int) ($limit['used'] ?? 0);
            $unlimited = $effective === -1;

            $limit['unlimited'] = $unlimited;
            $limit['percent'] = (! $unlimited && $effective > 0)
                ? min(100, round(($used / $effective) * 100, 1))
                : 0;

            return $limit;
        });

        try {
            $usageService->syncSnapshot((int) $tenant->id);
        } catch (\Throwable) {
            // Primary DB may be unavailable in some environments; live matrix still works.
        }

        $recentAuditLogs = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $apiTokens = TenantApiToken::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return view('central.pages.tenant-detail', [
            'tenant'             => $tenant,
            'pendingPayments'    => $pendingPayments,
            'invoices'           => $invoices,
            'featureSummary'     => collect($features->matrixForTenant($tenant))->filter(fn ($f) => $f['effective']),
            'limitSummary'       => $limitSummary,
            'hasOverrides'       => $featureOverrides->hasAnyOverride($tenant)
                || $limitOverrides->hasAnyOverride($tenant),
            'billingCurrency'    => TenantBillingRegion::currencyForTenant($tenant),
            'billingRegionLabel' => TenantBillingRegion::regionLabel($tenant),
            'recentAuditLogs'    => $recentAuditLogs,
            'apiTokens'          => $apiTokens,
        ]);
    }

    public function superTenantInvoiceShow(int $tenantId, int $invoiceId)
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $invoice = TenantInvoice::query()
            ->with(['payment', 'membership'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoiceId);

        return view('central.pages.tenant-invoice-show', [
            'tenant'  => $tenant,
            'invoice' => $invoice,
            'payment' => $invoice->payment,
        ]);
    }

    public function superTenantStatus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'status'  => ['required', 'string', Rule::in(['active', 'inactive', 'suspended', 'pending_email'])],
        ]);

        $tenant = Tenant::query()->find($validated['user_id']);

        if (! $tenant) {
            return response()->json(['success' => false], 404);
        }

        $before = $tenant->status;
        $tenant->update(['status' => $validated['status']]);

        $actor = Auth::guard('super_admin')->user();
        AuditLog::record(
            'tenant.status_changed',
            $tenant->id,
            'super_admin',
            $actor?->id,
            $actor?->name,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => "Status {$before} → {$validated['status']}",
                'before'       => ['status' => $before],
                'after'        => ['status' => $validated['status']],
            ]
        );

        return response()->json(['success' => true]);
    }
}
