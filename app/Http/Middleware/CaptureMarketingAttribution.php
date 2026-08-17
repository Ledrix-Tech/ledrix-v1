<?php

namespace App\Http\Middleware;

use App\Support\MarketingAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingAttribution
{
    public function handle(Request $request, Closure $next): Response
    {
        MarketingAttribution::capture($request);

        return $next($request);
    }
}
