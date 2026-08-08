<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Support\MarketingAttribution;

class LandingPagesController extends Controller
{
    public function trial()
    {
        MarketingAttribution::rememberLandingPath('/lp/agency-crm-trial');

        $package = $this->resolveTrialPackage();

        return view('front.pages.lp.agency-crm-trial', [
            'package'      => $package,
            'registerUrl'  => $package
                ? route('tenant.register.form', $package->slug)
                : route('pricing.get'),
            'trialDays'    => (int) ($package->trial_days ?? 14),
        ]);
    }

    public function demo()
    {
        MarketingAttribution::rememberLandingPath('/lp/demo');

        return view('front.pages.lp.demo');
    }

    public function demoThanks()
    {
        return view('front.pages.lp.demo-thanks');
    }

    private function resolveTrialPackage(): ?PackagePricing
    {
        $popular = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->first();

        if ($popular) {
            return $popular;
        }

        $configured = (string) config('marketing.default_trial_plan_slug', 'starter');

        $bySlug = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->where('slug', $configured)
            ->first();

        if ($bySlug) {
            return $bySlug;
        }

        return PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->first();
    }
}
