<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;

class ViewsController extends Controller
{
    public function showContactPage()
    {
        return view('front.pages.contact');
    }

    public function showPricingPage()
    {
        $packages = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        $limitRows = [
            'max_brands'          => 'Brands',
            'max_sellers'         => 'Sellers',
            'max_admins'          => 'Admins',
            'max_clients'         => 'Clients',
            'max_leads_per_month' => 'Leads / month',
            'max_orders'          => 'Orders',
            'max_payment_links'   => 'Payment links',
            'max_projects'        => 'Projects',
            'max_storage_mb'      => 'Storage (MB)',
        ];

        $featureRows = [
            'feature_ppc_module'          => 'PPC module',
            'feature_upwork_module'       => 'Upwork module',
            'feature_milestone_payments'  => 'Milestone payments',
            'feature_stripe'              => 'Stripe payments',
            'feature_paypal'              => 'PayPal payments',
            'feature_webhooks'            => 'Webhooks',
            'feature_chargeback_tracking' => 'Chargeback tracking',
            'feature_dual_invoicing'      => 'Dual invoicing',
            'feature_client_portal'       => 'Client portal',
            'feature_lead_prediction'   => 'Lead prediction',
            'feature_seller_leaderboard'  => 'Seller leaderboard',
            'feature_performance_bonus'   => 'Performance bonus',
            'feature_projects'            => 'Projects module',
            'feature_support_tickets'     => 'Support tickets',
            'feature_api_access'          => 'API access',
            'feature_custom_domain'       => 'Custom domain',
            'feature_white_label'         => 'White label',
        ];

        return view('front.pages.pricing', compact('packages', 'limitRows', 'featureRows'));
    }

    public function showFeaturesPage()
    {
        return view('front.pages.features');
    }

    public function showAboutPage()
    {
        return view('front.pages.about');
    }

    public function showFaqPage()
    {
        return view('front.pages.faq');
    }
}
