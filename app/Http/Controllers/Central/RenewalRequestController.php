<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantRenewalRequest;
use Illuminate\Support\Facades\Auth;

class RenewalRequestController extends Controller
{
    public function index()
    {
        $renewals = TenantRenewalRequest::query()
            ->with(['tenant:id,name,email,slug', 'plan:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('central.pages.renewal-requests', compact('renewals'));
    }

    public function cancel(int $id)
    {
        $renewal = TenantRenewalRequest::query()->findOrFail($id);

        if (! $renewal->isPending()) {
            return back()->with('error', 'Only pending renewal requests can be cancelled.');
        }

        $renewal->cancel();

        $actor = Auth::guard('super_admin')->user();

        AuditLog::record(
            'renewal_request.cancelled',
            $renewal->tenant_id,
            'super_admin',
            $actor?->id,
            $actor?->name,
            [
                'subject_type' => 'tenant_renewal_request',
                'subject_id'   => $renewal->id,
                'description'  => 'Cancelled pending renewal request #' . $renewal->id,
            ]
        );

        return back()->with('success', 'Renewal request cancelled.');
    }
}
