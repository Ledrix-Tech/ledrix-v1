<?php

namespace Tests\Unit;

use App\Support\PpcWebhookVerifier;
use Tests\TestCase;

class PpcWebhookVerifierTest extends TestCase
{
    public function test_extracts_paypal_capture_id_from_refund_link(): void
    {
        $verifier = new PpcWebhookVerifier(
            new \App\Services\PaymentGatewayFactory()
        );

        $resource = [
            'links' => [
                [
                    'rel'  => 'up',
                    'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE123',
                ],
            ],
            'amount' => ['value' => '25.00', 'currency_code' => 'USD'],
        ];

        $this->assertSame('CAPTURE123', $verifier->extractPaypalCaptureIdFromRefund($resource));
    }

    public function test_extracts_paypal_sale_id_fallback(): void
    {
        $verifier = new PpcWebhookVerifier(
            new \App\Services\PaymentGatewayFactory()
        );

        $resource = [
            'sale_id' => 'SALE456',
            'amount'  => ['value' => '10.00'],
        ];

        $this->assertSame('SALE456', $verifier->extractPaypalCaptureIdFromRefund($resource));
    }

    public function test_returns_null_when_paypal_refund_has_no_capture_reference(): void
    {
        $verifier = new PpcWebhookVerifier(
            new \App\Services\PaymentGatewayFactory()
        );

        $this->assertNull($verifier->extractPaypalCaptureIdFromRefund(['amount' => ['value' => '5.00']]));
    }
}
