<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paid ads / landing pages
    |--------------------------------------------------------------------------
    */

    /** Fallback plan slug when no popular public package exists. */
    'default_trial_plan_slug' => env('MARKETING_DEFAULT_TRIAL_PLAN', 'starter'),

    'meta_pixel_id' => env('META_PIXEL_ID'),

    'google_ads_id' => env('GOOGLE_ADS_ID'),

    /** Google Ads conversion label for trial start (optional). */
    'google_ads_trial_conversion_label' => env('GOOGLE_ADS_TRIAL_CONVERSION_LABEL'),

    /** Google Ads conversion label for demo lead (optional). */
    'google_ads_lead_conversion_label' => env('GOOGLE_ADS_LEAD_CONVERSION_LABEL'),

    'gtm_id' => env('GTM_ID'),

];
