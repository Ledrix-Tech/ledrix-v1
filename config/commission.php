<?php

return [
    // One policy active at a time. Pick one:
    'mode' => env('COMMISSION_MODE', 'count'), // 'count' | 'amount' | 'days'

    // Front seller gets credit for the first N successful payments
    'count' => [
        'payments' => (int) env('COMMISSION_COUNT_PAYMENTS', 1),
    ],

    // Front seller gets credit until they’ve earned this much (in cents) on the order
    'amount' => [
        'cents' => (int) env('COMMISSION_AMOUNT_CENTS', 0), // e.g. 50000 = $500
    ],

    // Front seller gets credit for Y days after first successful payment on this order
    'days' => [
        'window' => (int) env('COMMISSION_DAYS_WINDOW', 0), // e.g. 30
    ],
];
