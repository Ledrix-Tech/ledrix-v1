<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use Illuminate\Http\Request;

class OrganizationAuditLogController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(Request $request)
    {
        $tenant = $this->organizationTenant();

        $query = AuditLog::query()
            ->forTenant((int) $tenant->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.trim($request->action).'%');
        }

        if ($request->filled('actor_type')) {
            $query->where('actor_type', $request->actor_type);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")
                    ->orWhere('actor_name', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(30)->withQueryString();

        $actorTypes = AuditLog::query()
            ->forTenant((int) $tenant->id)
            ->select('actor_type')
            ->distinct()
            ->orderBy('actor_type')
            ->pluck('actor_type');

        return $this->organizationView('audit-logs', [
            'tenant'     => $tenant,
            'logs'       => $logs,
            'actorTypes' => $actorTypes,
            'filters'    => $request->only(['action', 'actor_type', 'q']),
        ]);
    }
}
