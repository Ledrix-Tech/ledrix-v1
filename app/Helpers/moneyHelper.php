<?php

if (!function_exists('money_cents')) {
    function money_cents(int $cents, string $currency = 'USD'): string
    {
        return number_format($cents / 100, 2) . ' ' . $currency;
    }
}


function getUserIp()
{
    $ip = request()->server('HTTP_CLIENT_IP')
        ?? request()->server('HTTP_X_FORWARDED_FOR')
        ?? request()->server('REMOTE_ADDR');

    // If multiple IPs (comma separated), take first one
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }

    return trim($ip);
}



function canGeneratePayLink($order): bool
{
    $seller = auth('seller')->user();
    $admin  = auth('admin')->check();

    // Admin always can
    if ($admin) {
        return $order->balance_due > 0 && $order->status !== 'paid';
    }

    // If not seller → cannot
    if (!$seller) {
        return false;
    }

    $role = $seller->role ?? $seller->is_seller;

    // ❌ PM should NEVER generate links
    if (isProjectManager()) {
        return false;
    }

    // Only FS can generate links
    $isFront = $role === 'front_seller';

    // FS can generate only inside their brand
    $sameBrand = (int) $seller->brand_id === (int) $order->brand_id;

    if (isFrontSeller() && $sameBrand) {
        return $order->balance_due > 0 && $order->status !== 'paid';
    }

    return false;
}
