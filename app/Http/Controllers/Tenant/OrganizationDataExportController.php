<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantDataExportRequest;
use App\Support\PlatformOpsNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationDataExportController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index()
    {
        $tenant = $this->organizationTenant();

        $exports = TenantDataExportRequest::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $current = $exports->first();

        return $this->organizationView('data-export', [
            'tenant'  => $tenant,
            'exports' => $exports,
            'current' => $current,
            'busy'    => TenantDataExportRequest::inProgressForTenant((int) $tenant->id) !== null,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->organizationTenant();

        if (TenantDataExportRequest::inProgressForTenant((int) $tenant->id)) {
            return $this->organizationRedirect('data-export', [], 'error', 'A data export is already pending or being prepared.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'             => $tenant->id,
            'requested_by_admin_id' => Auth::guard('admin')->id(),
            'requested_by_name'     => $actor?->name ?? $tenant->name,
            'requested_by_type'     => Auth::guard('admin')->check() ? 'admin' : 'tenant',
            'reason'                => $validated['reason'],
            'status'                => 'pending',
        ]);

        AuditLog::record(
            'tenant.data_export_requested',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant_data_export_request',
                'subject_id'   => $export->id,
                'description'  => 'Workspace data export requested',
            ]
        );

        PlatformOpsNotifier::alert(
            'tenant_data_export',
            'Data export requested by '.$tenant->name,
            [
                'tenant' => $tenant->name,
                'reason' => $validated['reason'],
                'url'    => route('super-admin.data-exports.get'),
            ]
        );

        return $this->organizationRedirect('data-export', [], 'success', 'Request submitted. Super Admin will review it before the package is prepared.');
    }

    public function download(Request $request, int $export): BinaryFileResponse
    {
        $tenant = $this->organizationTenant();
        $row = TenantDataExportRequest::query()->findOrFail($export);

        abort_unless((int) $row->tenant_id === (int) $tenant->id, 403);
        abort_unless($row->tenantCanDownload(), 403, 'This download link has expired or the file is not ready.');

        $path = $row->absolutePath();
        abort_unless($path && is_file($path), 404);

        $row->forceFill([
            'download_count'     => $row->download_count + 1,
            'last_downloaded_at' => now(),
        ])->save();

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/zip',
        ]);
    }
}
