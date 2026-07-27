<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

final class ClientPortalAuthorization
{
    public static function client(): Client
    {
        $client = Auth::guard('client')->user();
        abort_unless($client instanceof Client, 401, 'Client not authenticated.');

        return $client;
    }

    public static function assertOwnsOrder(Order $order): void
    {
        abort_unless(
            (int) $order->client_id === (int) self::client()->id,
            403,
            'You do not have access to this order.'
        );
    }

    public static function orderForClient(int $orderId): Order
    {
        return Order::query()
            ->where('client_id', self::client()->id)
            ->findOrFail($orderId);
    }
}
