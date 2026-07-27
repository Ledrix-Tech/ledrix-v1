<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminLeadsService;
use App\Support\PortalAuthorization;

class LeadsController extends Controller
{
    public function adminLeads(Request $request, AdminLeadsService $leads)
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (! $seller && ! $isAdmin) {
            return redirect()->route('admin.login.get')->with('error', 'You must be logged in.');
        }

        return view('admin.pages.leads', $leads->paginatedList($request));
    }

    public function adminLeadDetails($id, AdminLeadsService $leads)
    {
        PortalAuthorization::requirePortalActor();

        $payload = $leads->detailPayload((int) $id);
        $lead = $payload['lead'];

        $actor = auth('admin')->user() ?? auth('seller')->user();
        Gate::forUser($actor)->authorize('view', $lead);

        $user = auth('seller')->user();
        if ($user) {
            $lead->loadMissing('brand:id,brand_name');
            $brandSlug = Str::slug($lead->brand->brand_name ?? 'unknown-brand', '_');
            $logDir = storage_path("logs/brands/{$brandSlug}");

            if (! File::exists($logDir)) {
                File::makeDirectory($logDir, 0755, true);
            }

            $sessionKey = "viewed_lead_{$lead->id}";
            if (! session()->has($sessionKey)) {
                session()->put($sessionKey, now()->toDateTimeString());
                Log::build([
                    'driver' => 'single',
                    'path'   => "{$logDir}/lead-views.log",
                ])->info('Lead viewed', [
                    'seller_id'   => $user->id,
                    'seller_name' => $user->name,
                    'lead_id'     => $lead->id,
                    'lead_name'   => $lead->name,
                    'brand_name'  => $lead->brand->brand_name ?? 'N/A',
                    'viewed_at'   => now()->toDateTimeString(),
                    'ip'          => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                ]);
            }
        }

        return view('admin.pages.lead-details', $payload);
    }

    public function clearLeadViewLogs()
    {
        PortalAuthorization::requireAdmin();
        $directory = storage_path('logs/brands');

        try {
            if (File::exists($directory)) {
                File::deleteDirectory($directory);
            }

            File::makeDirectory($directory, 0755, true);

            return back()->with('success', 'All brand lead view logs cleared successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear logs: ' . $e->getMessage());
        }
    }

    public function sellerAssignedLeads(Request $request, AdminLeadsService $leads)
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (! $seller && ! $isAdmin) {
            return redirect()->route('admin.login.get')->with('error', 'You must be logged in.');
        }

        return view('admin.pages.assigned-leads', [
            'leads' => $leads->paginatedAssignedList($request),
        ]);
    }
}
