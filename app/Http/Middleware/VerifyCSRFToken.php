<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCSRFToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhooks/stripe',
        'webhooks/paypal',
        'billing/jazzcash/return',
        'billing/stripe/success',
        'billing/payfast/success',
    ];
}
