<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->with('tenant:id,name,email,slug')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', (int) $request->tenant_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . trim($request->action) . '%');
        }

        if ($request->filled('actor_type')) {
            $query->where('actor_type', $request->actor_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
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

        $tenants = Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $actorTypes = AuditLog::query()
            ->select('actor_type')
            ->distinct()
            ->orderBy('actor_type')
            ->pluck('actor_type');

        $filterTenant = $request->filled('tenant_id')
            ? $tenants->firstWhere('id', (int) $request->tenant_id)
            : null;

        $totalLogs = AuditLog::query()->count();
        $olderThan90 = AuditLog::query()
            ->where('created_at', '<', now()->subDays(90))
            ->count();

        return view('central.pages.audit-logs', compact(
            'logs',
            'tenants',
            'actorTypes',
            'filterTenant',
            'totalLogs',
            'olderThan90',
        ));
    }

    /**
     * Purge audit logs to keep the central DB lean.
     * Prefer deleting old rows; "all" requires an explicit confirm phrase.
     */
    public function clear(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['older_than', 'all'])],
            'days' => ['nullable', 'integer', Rule::in([30, 90, 180, 365])],
            'confirm_text' => ['nullable', 'string', 'max:20'],
        ]);

        $actor = Auth::guard('super_admin')->user();
        $query = AuditLog::query();

        if ($validated['mode'] === 'older_than') {
            $days = (int) ($validated['days'] ?? 90);
            $cutoff = now()->subDays($days);
            $deleted = (clone $query)->where('created_at', '<', $cutoff)->delete();
            $message = "Deleted {$deleted} audit log(s) older than {$days} days.";
        } else {
            if (strtoupper(trim((string) ($validated['confirm_text'] ?? ''))) !== 'DELETE') {
                return back()->with('error', 'Type DELETE to confirm clearing all audit logs.');
            }

            $deleted = $query->delete();
            $message = "Deleted all {$deleted} audit log(s).";
        }

        Log::warning('Super admin cleared audit logs', [
            'mode'       => $validated['mode'],
            'days'       => $validated['days'] ?? null,
            'deleted'    => $deleted,
            'actor_id'   => $actor?->id,
            'actor_name' => $actor?->name,
            'ip'         => $request->ip(),
        ]);

        return back()->with('success', $message);
    }
}
