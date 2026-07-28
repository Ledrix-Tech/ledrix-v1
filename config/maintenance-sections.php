<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Section maintenance (admin, seller, client, front)
    |--------------------------------------------------------------------------
    |
    | Toggle with: php artisan admin-web-down / admin-web-up (and seller, client, front).
    | Independent from global `php artisan down` / `up`.
    |
    */

    'cache_prefix' => 'section-maintenance',

    'bypass_paths' => [
        'web-pick',
        'api/*',
        'pay/*',
        'brand/*',
        'upwork-pay/*',
    ],

    'sections' => [
        'admin' => [
            'label' => 'Admin panel',
            'paths' => ['admin', 'admin/*'],
        ],
        'seller' => [
            'label' => 'Seller portal',
            'paths' => ['seller', 'seller/*'],
        ],
        'client' => [
            'label' => 'Client portal',
            'paths' => ['client', 'client/*'],
        ],
        'front' => [
            'label' => 'Website',
            'paths' => [
                '/',
                'crm',
                'nexus',
                'contact-us',
                'pricing',
                'features',
                'about',
                'faq',
                'sitemap.xml',
                'robots.txt',
                'contact',
                'renew/approve/*',
            ],
        ],
    ],

];
