<?php

function sellerLoginHoursEnabled(): bool
{
    return filter_var(env('SELLER_RESTRICT_LOGIN_HOURS', false), FILTER_VALIDATE_BOOLEAN);
}

function isWithinWorkingHours(): bool
{
    if (! sellerLoginHoursEnabled()) {
        return true;
    }

    $now = now()->timezone('Asia/Karachi');
    $start = $now->copy()->setTime(9, 0);
    $end = $now->copy()->setTime(18, 0);

    return $now->between($start, $end);
}

function isOfficeIp(): bool
{
    $allowedIps = array_filter(array_map('trim', explode(',', (string) env('SELLER_OFFICE_IPS', '119.73.104.124'))));

    return in_array(request()->ip(), $allowedIps, true);
}
