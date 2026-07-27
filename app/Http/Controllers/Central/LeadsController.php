<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\LeadClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadsController extends Controller
{
    public function checkCompanyData(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $company = Tenant::where('email', $data['email'])
            ->where('status', 'active')
            ->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'No active tenant found for this email',
            ], 404);
        }

        $activeFeatures = $company->featureFlags()
            ->where('is_enabled', true)
            ->pluck('feature_key');

        if ($activeFeatures->isEmpty() && ! $company->activeMembership) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant found but no active subscription/features',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_id' => $company->id,
                'email'     => $company->email,
                'features'  => $activeFeatures,
            ],
        ]);
    }

    public function classifyLead(Request $request, LeadClassifier $classifier)
    {
        $data = $request->validate([
            'email'   => 'required|email|max:255',
            'service' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:30',
            'message' => 'nullable|string|max:4000',
        ]);

        Log::info('API classifyLead input', $data);

        /** @var Tenant|null $company */
        $company = $request->attributes->get('company');
        if (! $company) {
            return response()->json([
                'success' => false,
                'error'   => 'Unauthorized: Invalid API key',
            ], 401);
        }

        $hasFeature = $company->featureFlags()
            ->where('feature_key', 'leads-classify')
            ->where('is_enabled', true)
            ->exists();

        if (! $hasFeature && ! ($company->plan?->feature_lead_prediction ?? false)) {
            return response()->json([
                'success' => false,
                'error'   => 'Leads classify feature is not active for this tenant',
            ], 403);
        }

        $result = $classifier->classify($data);
        Log::info('Classifier result', $result);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
