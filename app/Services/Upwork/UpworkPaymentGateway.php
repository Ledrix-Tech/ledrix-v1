<?php

namespace App\Services\Upwork;

use App\Models\Upwork\UpworkPaymentLink;

interface UpworkPaymentGateway
{
    /** Create a hosted checkout for this link and return ['id'=>..., 'url'=>...] */
    public function createCheckout(UpworkPaymentLink $link, array $buyer): array;

    /** Webhook entry point. Must be idempotent. Return true when processed. */
    public function handleWebhook(string $payload, array $headers): bool;

    /** Fallback for success page when webhook is delayed. */
    public function handleCheckoutSuccess(UpworkPaymentLink $link, ?string $sessionId): void;

}
