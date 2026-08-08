<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'meta' => [
        // Prefer config('marketing.meta_pixel_id') in views; kept for callers using services.meta.
        'pixel_id' => env('META_PIXEL_ID'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')
    ],

    // 'stripe' => [
    //     'nexus' => [
    //         'key' => env('STRIPE_KEY_NEXUS'),
    //         'secret' => env('STRIPE_SECRET_NEXUS'),
    //     ],
    //     'devxperts' => [
    //         'key' => env('STRIPE_KEY_DEVXPERTS'),
    //         'secret' => env('STRIPE_SECRET_DEVXPERTS'),
    //     ],
    // ],

    'paypal' => [
        // sandbox: https://api-m.sandbox.paypal.com
        // live:    https://api-m.paypal.com
        'base'       => env('PAYPAL_BASE', 'https://api-m.sandbox.paypal.com'),
        'client_id'  => env('PAYPAL_CLIENT_ID'),
        'secret'     => env('PAYPAL_SECRET'),
        // optional but recommended for signature verification
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],


    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'recaptcha' => [
        'key'    => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
    ],

    'payoneer' => [
        'receiver_email' => env('PAYONEER_RECEIVER_EMAIL'),
        'receiver_name'  => env('PAYONEER_RECEIVER_NAME', 'Ledrix'),
        'currency'       => env('PAYONEER_CURRENCY', 'USD'),
        'grace_days'     => (int) env('PAYONEER_GRACE_DAYS', 7),
        'trial_reminder_days' => (int) env('PAYONEER_TRIAL_REMINDER_DAYS', 3),
    ],

    /*
    | JazzCash Merchant Gateway — tenant SaaS subscriptions (PKR).
    | Sandbox: https://sandbox.jazzcash.com.pk/MerchantDashboard/
    | Credentials go in .env (see JAZZCASH_* below).
    */
    'jazzcash' => [
        'sandbox'          => (bool) env('JAZZCASH_SANDBOX', true),
        // Public JazzCash sandbox demo credentials (from official docs) — replace with yours from Merchant Dashboard
        'merchant_id'      => env('JAZZCASH_MERCHANT_ID', env('JAZZCASH_SANDBOX', true) ? '00127801' : null),
        'password'         => env('JAZZCASH_PASSWORD', env('JAZZCASH_SANDBOX', true) ? '0123456789' : null),
        'integrity_salt'   => env('JAZZCASH_INTEGRITY_SALT', env('JAZZCASH_SANDBOX', true) ? '0123456789' : null),
        'return_url'       => env('JAZZCASH_RETURN_URL'), // defaults to APP_URL/billing/jazzcash/return
        'currency'         => env('JAZZCASH_CURRENCY', 'PKR'),
        'usd_to_pkr_rate'  => (float) env('JAZZCASH_USD_TO_PKR_RATE', 280),
        'trial_reminder_days' => (int) env('JAZZCASH_TRIAL_REMINDER_DAYS', 3),
        'grace_days'       => (int) env('JAZZCASH_GRACE_DAYS', 7),
    ],

    'bank_transfer' => [
        'usd' => [
            'bank_name'      => env('BANK_TRANSFER_USD_BANK_NAME'),
            'account_title'  => env('BANK_TRANSFER_USD_ACCOUNT_TITLE'),
            'account_number' => env('BANK_TRANSFER_USD_ACCOUNT_NUMBER'),
            'iban'           => env('BANK_TRANSFER_USD_IBAN'),
            'swift'          => env('BANK_TRANSFER_USD_SWIFT'),
        ],
        'pkr' => [
            'bank_name'      => env('MEEZAN_BANK_NAME', env('BANK_TRANSFER_PKR_BANK_NAME', 'Meezan Bank')),
            'account_title'  => env('MEEZAN_ACCOUNT_TITLE', env('BANK_TRANSFER_PKR_ACCOUNT_TITLE')),
            'account_number' => env('MEEZAN_ACCOUNT_NUMBER', env('BANK_TRANSFER_PKR_ACCOUNT_NUMBER')),
            'iban'           => env('MEEZAN_IBAN', env('BANK_TRANSFER_PKR_IBAN')),
            'branch'         => env('MEEZAN_BRANCH', env('BANK_TRANSFER_PKR_BRANCH')),
            'merchant_city'  => env('MEEZAN_MERCHANT_CITY', 'Karachi'),
        ],
        'raast_account_tag' => env('RAAST_QR_ACCOUNT_TAG', '28'),
        'raast_qr_mode' => env('RAAST_QR_MODE', 'dynamic'), // dynamic | static
        'qr_expiry_days' => (int) env('RAAST_QR_EXPIRY_DAYS', 7),
        'grace_days' => (int) env('BANK_TRANSFER_GRACE_DAYS', 7),
        'dev_auto_confirm' => (bool) env('BILLING_DEV_AUTO_CONFIRM_BANK', false),
        'notify_email' => env('BILLING_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

    /*
    | PayFast Pakistan — automated PKR checkout (cards, wallets, Raast).
    | Sign up: https://gopayfast.com/ — link your Meezan account as settlement bank in their portal.
    | API docs: https://gopayfast.com/docs/
    */
    'payfast' => [
        'mode'          => env('PAYFAST_MODE', 'sandbox'),
        'merchant_id'   => env('PAYFAST_MERCHANT_ID'),
        'merchant_name' => env('PAYFAST_MERCHANT_NAME', env('APP_NAME', 'Ledrix')),
        'secured_key'   => env('PAYFAST_SECURED_KEY'),
        'grant_type'    => env('PAYFAST_GRANT_TYPE', 'client_credentials'),
        'token_url'     => env('PAYFAST_TOKEN_URL'),
        'checkout_url'  => env('PAYFAST_CHECKOUT_URL'),
        'return_url'    => env('PAYFAST_RETURN_URL'),
    ],

];
