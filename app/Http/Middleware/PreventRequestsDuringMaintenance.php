<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as BasePreventRequestsDuringMaintenance;

class PreventRequestsDuringMaintenance extends BasePreventRequestsDuringMaintenance
{
    protected $except = [
        'site-up', // allow site-up route during maintenance mode
    ];


    // public function handle($request, Closure $next)
    // {
    //     // Laravel will automatically inject $this->app from the Base class
    //     if ($this->app->isDownForMaintenance()) {
    //         Log::info('Blocked path in maintenance mode: ' . $request->path());

    //         // allow toggle-up and toggle-down
    //         if (!in_array($request->path(), ['toggle-up', 'toggle-down'])) {
    //             throw new HttpException(503);
    //         }
    //     }

    //     return $next($request);
    // }
}
