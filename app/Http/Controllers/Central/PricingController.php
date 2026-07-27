<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PricingController extends Controller
{
    public function superPackegsPricing()
    {
        $packages = PackagePricing::all();
        return view('central.pages.pricing-packages', compact('packages'));
    }

    public function pricingPackageStore(Request $request)
    {
        $request->validate([
            // Basic
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('central.package_pricings', 'slug'),
            ],
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'features_html' => 'nullable|string',

            // Limits
            'max_brands' => 'required|integer|min:-1',
            'max_sellers' => 'required|integer|min:-1',
            'max_admins' => 'required|integer|min:-1',
            'max_clients' => 'required|integer|min:-1',
            'max_leads_per_month' => 'required|integer|min:-1',
            'max_orders' => 'required|integer|min:-1',
            'max_payment_links' => 'required|integer|min:-1',
            'max_account_keys' => 'required|integer|min:-1',
            'max_projects' => 'required|integer|min:-1',
            'max_storage_mb' => 'required|integer|min:0',
        ]);

        try {

            DB::connection('central')->beginTransaction();

            PackagePricing::create([

                // Basic
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'features_html' => $request->features_html,

                // Pricing
                'monthly_price' => $request->monthly_price,
                'yearly_price' => $request->yearly_price ?: ($request->monthly_price * 12),
                'currency' => 'USD',
                'trial_days' => 14,

                // Limits
                'max_brands' => $request->max_brands,
                'max_sellers' => $request->max_sellers,
                'max_admins' => $request->max_admins,
                'max_clients' => $request->max_clients,
                'max_leads_per_month' => $request->max_leads_per_month,
                'max_orders' => $request->max_orders,
                'max_payment_links' => $request->max_payment_links,
                'max_account_keys' => $request->max_account_keys,
                'max_projects' => $request->max_projects,
                'max_storage_mb' => $request->max_storage_mb,

                // Features (unchecked checkboxes become false)
                'feature_ppc_module' => $request->boolean('feature_ppc_module'),
                'feature_upwork_module' => $request->boolean('feature_upwork_module'),
                'feature_milestone_payments' => $request->boolean('feature_milestone_payments'),
                'feature_stripe' => $request->boolean('feature_stripe'),
                'feature_paypal' => $request->boolean('feature_paypal'),
                'feature_webhooks' => $request->boolean('feature_webhooks'),
                'feature_chargeback_tracking' => $request->boolean('feature_chargeback_tracking'),
                'feature_dual_invoicing' => $request->boolean('feature_dual_invoicing'),
                'feature_client_portal' => $request->boolean('feature_client_portal'),
                'feature_lead_prediction' => $request->boolean('feature_lead_prediction'),
                'feature_seller_leaderboard' => $request->boolean('feature_seller_leaderboard'),
                'feature_performance_bonus' => $request->boolean('feature_performance_bonus'),
                'feature_projects' => $request->boolean('feature_projects'),
                'feature_support_tickets' => $request->boolean('feature_support_tickets'),
                'feature_api_access' => $request->boolean('feature_api_access'),
                'feature_custom_domain' => $request->boolean('feature_custom_domain'),
                'feature_white_label' => $request->boolean('feature_white_label'),

                // // Display
                // 'is_popular' => $request->boolean('is_popular'),
                // 'is_public' => $request->boolean('is_public', true),
                // 'sort_order' => $request->sort_order ?? 0,
                // 'badge_text' => $request->badge_text,
                // 'status' => $request->status ?? 'active',
            ]);

            DB::connection('central')->commit();

            return redirect()->back()->with('success', 'Package created successfully.');
        } catch (\Exception $e) {

            DB::connection('central')->rollBack();

            Log::error('Pricing Package Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()
                ->with('error', 'Something went wrong while creating the package.');
        }
    }

    public function pricingPackageUpdate(Request $request, $id)
    {
        try {

            $package = PackagePricing::findOrFail($id);

            $request->validate([

                // Basic
                'name' => 'required|string|max:255',

                'slug' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('central.package_pricings', 'slug')->ignore($package->id),
                ],

                'monthly_price' => 'required|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'features_html' => 'nullable|string',

                // Limits
                'max_brands' => 'required|integer|min:-1',
                'max_sellers' => 'required|integer|min:-1',
                'max_admins' => 'required|integer|min:-1',
                'max_clients' => 'required|integer|min:-1',
                'max_leads_per_month' => 'required|integer|min:-1',
                'max_orders' => 'required|integer|min:-1',
                'max_payment_links' => 'required|integer|min:-1',
                'max_account_keys' => 'required|integer|min:-1',
                'max_projects' => 'required|integer|min:-1',
                'max_storage_mb' => 'required|integer|min:0',

                // Display
                'trial_days' => 'nullable|integer|min:0',
                'badge_text' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'required|in:active,inactive',

            ]);

            DB::connection('central')->beginTransaction();

            $package->update([

                // Basic
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'features_html' => $request->features_html,

                // Pricing
                'monthly_price' => $request->monthly_price,
                'yearly_price' => $request->yearly_price ?: ($request->monthly_price * 12),
                'currency' => 'USD',
                'trial_days' => $request->trial_days ?? 14,

                // Limits
                'max_brands' => $request->max_brands,
                'max_sellers' => $request->max_sellers,
                'max_admins' => $request->max_admins,
                'max_clients' => $request->max_clients,
                'max_leads_per_month' => $request->max_leads_per_month,
                'max_orders' => $request->max_orders,
                'max_payment_links' => $request->max_payment_links,
                'max_account_keys' => $request->max_account_keys,
                'max_projects' => $request->max_projects,
                'max_storage_mb' => $request->max_storage_mb,

                // Feature Flags
                'feature_ppc_module' => $request->boolean('feature_ppc_module'),
                'feature_upwork_module' => $request->boolean('feature_upwork_module'),
                'feature_milestone_payments' => $request->boolean('feature_milestone_payments'),
                'feature_stripe' => $request->boolean('feature_stripe'),
                'feature_paypal' => $request->boolean('feature_paypal'),
                'feature_webhooks' => $request->boolean('feature_webhooks'),
                'feature_chargeback_tracking' => $request->boolean('feature_chargeback_tracking'),
                'feature_dual_invoicing' => $request->boolean('feature_dual_invoicing'),
                'feature_client_portal' => $request->boolean('feature_client_portal'),
                'feature_lead_prediction' => $request->boolean('feature_lead_prediction'),
                'feature_seller_leaderboard' => $request->boolean('feature_seller_leaderboard'),
                'feature_performance_bonus' => $request->boolean('feature_performance_bonus'),
                'feature_projects' => $request->boolean('feature_projects'),
                'feature_support_tickets' => $request->boolean('feature_support_tickets'),
                'feature_api_access' => $request->boolean('feature_api_access'),
                'feature_custom_domain' => $request->boolean('feature_custom_domain'),
                'feature_white_label' => $request->boolean('feature_white_label'),

                // Display
                'is_popular' => $request->boolean('is_popular'),
                'is_public' => $request->boolean('is_public'),
                'sort_order' => $request->sort_order ?? 0,
                'badge_text' => $request->badge_text,
                'status' => $request->status,

            ]);

            DB::connection('central')->commit();

            return redirect()
                ->back()
                ->with('success', 'Package updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {

            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return redirect()
                ->back()
                ->with('error', 'Package not found.');
        } catch (\Exception $e) {

            DB::connection('central')->rollBack();

            Log::error('Pricing Package Update Error', [
                'package_id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong while updating the package.');
        }
    }
}
