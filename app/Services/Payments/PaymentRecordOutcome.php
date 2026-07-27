<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * Result of a webhook-safe payment record attempt.
 * Guides HTTP response: retry only on FAILED_RETRY.
 */
class PaymentRecordOutcome
{
    public const OK_NEW = 'ok_new';

    public const OK_DUPLICATE = 'ok_duplicate';

    public const SKIPPED = 'skipped';

    public const FAILED_RETRY = 'failed_retry';

    public function __construct(
        public readonly string $status,
        public readonly ?Payment $payment = null,
        public readonly ?Order $order = null,
        public readonly bool $isNew = false,
        public readonly ?string $message = null,
    ) {}

    public function shouldRetryWebhook(): bool
    {
        return $this->status === self::FAILED_RETRY;
    }

    public function acknowledged(): bool
    {
        return ! $this->shouldRetryWebhook();
    }
}
