<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\TenantRegistrationRequest;
use App\Models\Central\PackagePricing;
use App\Services\Central\TenantRegistrationService;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function storeCompany(
        Request $request,
        TenantRegistrationService $tenantRegistrationService
    ) {

        $validated = $request->validate([
            'pkg_slug' => 'required|string|exists:central.package_pricings,slug',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:central.tenants,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'nullable|string|max:20',
            'billing_address' => 'required|string|max:500',
            'country' => 'required|string|max:5',
        ]);
        try {

            $tenant = $tenantRegistrationService->register($validated);
            session([
                'company_plain_password' => $tenant->plain_password
            ]);
            return redirect()->route('company.checkout.start', $tenant->id);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function showCompanyForm($slug)
    {
        $pkg = PackagePricing::where('slug', $slug)->first();
        // dd($pkg);
        return view('front.pages.company-form', compact('pkg'));
    }

    public function companyProfile()
    {
        $company = auth('companies')->user(); // already a company instance
        // Get the latest membership with its package
        $subscription = $company->activeMembership()
            ->with('package')
            ->latest()
            ->first();
        $limits = $company->systemLimit; // or ->systemLimit()->first();
        // dd($company, $subscription, $limits);

        return view('front.pages.company-profile', compact('company', 'subscription', 'limits'));
    }
}
