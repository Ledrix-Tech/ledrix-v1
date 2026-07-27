<?php

namespace App\Services;

use App\Models\PaymentLink;

interface PaymentGateway
{
    /** Create a hosted checkout for this link and return ['id'=>..., 'url'=>...] */
    public function createCheckout(PaymentLink $link, array $buyer): array;

    /** Webhook entry point. Must be idempotent. Return true when processed. */
    public function handleWebhook(string $payload, array $headers): bool;

    /** Fallback for success page when webhook is delayed. */
    public function handleCheckoutSuccess(PaymentLink $link, ?string $sessionId): void;

}
