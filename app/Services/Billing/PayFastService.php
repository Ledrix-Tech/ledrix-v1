<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayFastService
{
    public function isConfigured(): bool
    {
        return app(PlatformBillingSettingsService::class)->isReady('payfast');
    }

    public function getAccessToken(): ?string
    {
        $tokenUrl = config('services.payfast.token_url');

        if (! $tokenUrl) {
            return null;
        }

        $response = Http::asForm()->post($tokenUrl, [
            'merchant_id'  => config('services.payfast.merchant_id'),
            'secured_key'  => config('services.payfast.secured_key'),
            'grant_type'   => config('services.payfast.grant_type', 'client_credentials'),
        ]);

        if (! $response->successful()) {
            Log::warning('PayFast token request failed', ['body' => $response->body()]);

            return null;
        }

        $data = $response->json();

        return $data['token'] ?? $data['access_token'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildHostedCheckoutFields(
        Tenant $tenant,
        TenantPayment $payment,
        string $accessToken,
        ?string $failUrl = null,
    ): array {
        $merchantId = config('services.payfast.merchant_id');
        $merchantName = config('services.payfast.merchant_name', config('app.name', 'Ledrix'));
        $amount = number_format((float) $payment->amount, 2, '.', '');
        $orderId = $payment->transaction_id;

        $signature = md5("{$merchantId}:{$merchantName}:{$amount}:{$orderId}");

        $successUrl = route('tenant.billing.payfast.success');
        $failUrl = $failUrl ?: (route('tenant.billing') . '?cancelled=1');
        $backendCallback = 'signature=' . $signature . '&order_id=' . $orderId;

        return [
            'MERCHANT_ID'            => $merchantId,
            'MERCHANT_NAME'          => $merchantName,
            'TOKEN'                  => $accessToken,
            'PROCCODE'               => '00',
            'TXNAMT'                 => $amount,
            'CUSTOMER_MOBILE_NO'     => $tenant->phone ?? $tenant->billing_phone ?? '',
            'CUSTOMER_EMAIL_ADDRESS' => $tenant->email,
            'SIGNATURE'              => $signature,
            'VERSION'                => 'LEDrix-SUB-1.0',
            'TXNDESC'                => 'Ledrix subscription — ' . ($tenant->plan?->name ?? 'CRM'),
            'SUCCESS_URL'            => $successUrl,
            'FAILURE_URL'            => $failUrl,
            'BASKET_ID'              => $orderId,
            'ORDER_DATE'             => now()->format('Y-m-d H:i:s'),
            'CHECKOUT_URL'           => $backendCallback,
        ];
    }

    public function checkoutUrl(): string
    {
        $url = config('services.payfast.checkout_url');

        if (! $url) {
            throw new RuntimeException('PayFast checkout URL is not configured.');
        }

        return $url;
    }

    public function verifySignature(string $merchantId, string $merchantName, string $amount, string $orderId, string $signature): bool
    {
        $expected = md5("{$merchantId}:{$merchantName}:{$amount}:{$orderId}");

        return hash_equals($expected, $signature);
    }
}
