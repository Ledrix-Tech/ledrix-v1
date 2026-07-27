<?php

namespace App\Services\Billing;

use App\Models\Central\TenantPayment;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;
use RuntimeException;

class BankTransferQrService
{
    public function __construct(
        private readonly RaastEmvQrBuilder $raastBuilder,
    ) {}

    /**
     * @return array{data_uri: string, payload: string, raast: bool, mode: string}
     */
    public function generate(TenantPayment $payment, array $bank): array
    {
        $payload = $this->buildPayload($payment, $bank);
        $mode = (string) config('services.bank_transfer.raast_qr_mode', 'dynamic');

        $outputClass = extension_loaded('gd')
            ? QRGdImagePNG::class
            : QRMarkupSVG::class;

        $options = new QROptions([
            'outputInterface' => $outputClass,
            'outputBase64'    => true,
            'scale'           => 8,
            'addQuietzone'    => true,
            'eccLevel'        => EccLevel::M,
        ]);

        return [
            'data_uri' => (new QRCode($options))->render($payload),
            'payload'  => $payload,
            'raast'    => str_starts_with($payload, '000202'),
            'mode'     => $mode,
        ];
    }

    public function dataUri(TenantPayment $payment, array $bank): string
    {
        return $this->generate($payment, $bank)['data_uri'];
    }

    public function canGenerateRaastQr(array $bank): bool
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($bank['iban'] ?? '')));

        return (bool) preg_match('/^PK[0-9]{2}[A-Z0-9]{20}$/', $iban);
    }

    public function buildPayload(TenantPayment $payment, array $bank): string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($bank['iban'] ?? '')));

        if (! $this->canGenerateRaastQr($bank)) {
            throw new RuntimeException(
                'Raast QR requires a valid Pakistan IBAN (24 characters). Add MEEZAN_IBAN to .env.'
            );
        }

        $mode = (string) config('services.bank_transfer.raast_qr_mode', 'dynamic');
        $expiresAt = $payment->invoice?->due_at ?? now()->addDays((int) config('services.bank_transfer.qr_expiry_days', 7));

        try {
            return $this->raastBuilder->buildForPayment(
                iban: $iban,
                amount: (string) $payment->amount,
                merchantName: (string) ($bank['account_title'] ?? config('app.name', 'Ledrix')),
                merchantCity: (string) ($bank['merchant_city'] ?? config('services.bank_transfer.pkr.merchant_city', 'Karachi')),
                referenceLabel: $payment->transaction_id,
                billNumber: $payment->invoice?->invoice_number,
                expiresAt: $expiresAt,
                mode: $mode,
            );
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
