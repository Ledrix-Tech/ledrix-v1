<?php

namespace App\Http\Middleware;

use Closure;

class RestrictToMyIp
{
    public function handle($request, Closure $next)
    {
        $requestIp = $request->ip();

        // Always allow localhost
        $allowedIps = [
            '127.0.0.1',  // local dev
            '::1',        // IPv6 localhost
            '223.123.114.232', // My mobile network zng IP
            '', // My laptop network zng IP via hotspot
            '192.168.18.*',  // your current WiFi / LAN
            '192.168.10.*',  // 2nd office connection (example)
            '10.0.0.*',      // 3rd office network (example)
            '119.73.104.71', // static public office IP (if any)
        ];

        // Allow local LAN ranges (office WiFi/Ethernet)
        if (preg_match('/^(192\.168\.|10\.)/', $requestIp)) {
            return $next($request);
        }

        // Dynamically fetch current public IP of office
        try {
            $publicIp = trim(file_get_contents('https://ifconfig.me/ip'));
            $allowedIps[] = $publicIp;
        } catch (\Exception $e) {
            // If lookup fails, block request (or log it)
        }

        if (in_array($requestIp, $allowedIps)) {
            return $next($request);
        }

        abort(403, "Access restricted. Your IP ($requestIp) is not allowed.");
    }
}


