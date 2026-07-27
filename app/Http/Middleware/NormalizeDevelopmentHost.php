<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local dev: keep session/CSRF on one host (localhost vs 127.0.0.1 breaks cookies).
 */
class NormalizeDevelopmentHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            return $next($request);
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());

        $host = $request->getHost();

        if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $next($request);
        }

        $preferredHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! in_array($preferredHost, ['localhost', '127.0.0.1'], true)) {
            $preferredHost = '127.0.0.1';
        }

        if ($host === $preferredHost) {
            return $next($request);
        }

        $url = $request->getScheme() . '://' . $preferredHost;
        $port = $request->getPort();

        if ($port && ! in_array($port, [80, 443], true)) {
            $url .= ':' . $port;
        }

        return redirect()->to($url . $request->getRequestUri());
    }
}
