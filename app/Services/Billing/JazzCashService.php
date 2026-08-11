<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Config;

class JazzCashService
{
    public function isConfigured(): bool
    {
        return app(PlatformBillingSettingsService::class)->isReady('jazzcash');
    }

    public function checkoutUrl(): string
    {
        if (config('services.jazzcash.sandbox', true)) {
            return 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
        }

        return 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
    }

    public function tokenChargeUrl(): string
    {
        if (config('services.jazzcash.sandbox', true)) {
            return 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/purchase/domwallettransactionviatoken';
        }

        return 'https://payments.jazzcash.com.pk/ApplicationAPI/API/2.0/purchase/domwallettransactionviatoken';
    }

    public function returnUrl(): string
    {
        return config('services.jazzcash.return_url')
            ?: url('/billing/jazzcash/return');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCheckoutFields(
        string $txnRefNo,
        float $amountPkr,
        string $description,
        string $billReference,
        bool $recurring = false,
    ): array {
        $now = now();
        $fields = [
            'pp_Version'             => '1.1',
            'pp_TxnType'             => 'MWALLET',
            'pp_Language'            => 'EN',
            'pp_MerchantID'          => $this->merchantId(),
            'pp_SubMerchantID'       => '',
            'pp_Password'            => $this->password(),
            'pp_BankID'              => 'TBANK',
            'pp_ProductID'           => 'RETL',
            'pp_TxnRefNo'            => $txnRefNo,
            'pp_Amount'              => (string) ((int) round($amountPkr * 100)),
            'pp_TxnCurrency'         => config('services.jazzcash.currency', 'PKR'),
            'pp_TxnDateTime'         => $now->format('YmdHis'),
            'pp_TxnExpiryDateTime'   => $now->copy()->addDay()->format('YmdHis'),
            'pp_BillReference'       => $billReference,
            'pp_Description'         => mb_substr($description, 0, 200),
            'pp_ReturnURL'           => $this->returnUrl(),
            'ppmpf_1'                => $billReference,
        ];

        if ($recurring) {
            $fields['pp_Frequency'] = 'RECURRING';
        }

        $fields['pp_SecureHash'] = $this->generateSecureHash($fields);

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function verifyResponseHash(array $response): bool
    {
        $received = strtoupper((string) ($response['pp_SecureHash'] ?? ''));

        if ($received === '') {
            return false;
        }

        $expected = $this->generateSecureHash($response);

        return hash_equals($expected, $received);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function isSuccessfulResponse(array $response): bool
    {
        $code = (string) ($response['pp_ResponseCode'] ?? '');

        return in_array($code, ['000', '121'], true);
    }

    /**
     * @return array{success: bool, response: array<string, mixed>|null, message: string}
     */
    public function chargeViaToken(string $token, string $txnRefNo, float $amountPkr, string $description): array
    {
        $fields = [
            'pp_Version'       => '1.1',
            'pp_TxnType'       => 'MWALLET',
            'pp_Language'      => 'EN',
            'pp_MerchantID'    => $this->merchantId(),
            'pp_Password'      => $this->password(),
            'pp_TxnRefNo'      => $txnRefNo,
            'pp_Amount'        => (string) ((int) round($amountPkr * 100)),
            'pp_TxnCurrency'   => config('services.jazzcash.currency', 'PKR'),
            'pp_TxnDateTime'   => now()->format('YmdHis'),
            'pp_Description'   => mb_substr($description, 0, 200),
            'pp_PaymentToken'  => $token,
        ];

        $fields['pp_SecureHash'] = $this->generateSecureHash($fields);

        $response = $this->postForm($this->tokenChargeUrl(), $fields);

        if (! is_array($response)) {
            return [
                'success'  => false,
                'response' => null,
                'message'  => 'Invalid response from JazzCash token API.',
            ];
        }

        $verified = $this->verifyResponseHash($response);
        $success = $verified && $this->isSuccessfulResponse($response);

        return [
            'success'  => $success,
            'response' => $response,
            'message'  => (string) ($response['pp_ResponseMessage'] ?? ($success ? 'OK' : 'Charge failed')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateSecureHash(array $data): string
    {
        unset($data['pp_SecureHash']);
        ksort($data);

        $string = $this->integritySalt();

        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                $string .= '&' . $value;
            }
        }

        return strtoupper(hash_hmac('sha256', $string, $this->integritySalt()));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>|null
     */
    private function postForm(string $url, array $fields): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error) {
            return null;
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        parse_str($body, $parsed);

        return is_array($parsed) && $parsed !== [] ? $parsed : null;
    }

    private function merchantId(): ?string
    {
        return Config::get('services.jazzcash.merchant_id');
    }

    private function password(): ?string
    {
        return Config::get('services.jazzcash.password');
    }

    private function integritySalt(): ?string
    {
        return Config::get('services.jazzcash.integrity_salt');
    }
}
