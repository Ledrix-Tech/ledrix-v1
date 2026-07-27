<?php

namespace App\Services\Upwork;

use App\Models\Brand;
use Illuminate\Support\Str;
use App\Models\Upwork\UpworkOrder;
use Illuminate\Support\Facades\DB;
use App\Models\Upwork\UpworkClient;
use Illuminate\Support\Facades\Log;
use App\Models\Upwork\UpworkPaymentLink;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\ModuleMismatchException;
use App\Exceptions\OrderAlreadyPaidException;
use App\Support\LinkStatus;
use App\Support\ModuleType;
use App\Support\OrderStatus;
use Illuminate\Database\QueryException;

class UpworkLinkGenerator
{
    public function createOriginalOrderAndFirstLink(
        Brand $brand,
        array $clientData,
        string $sellType,
        string $serviceName,
        string $currency,
        int $totalCents,
        int $payNowCents,
        string $provider,
        int $expiresInHours = 168,
        $generatedBy = null
    ): UpworkPaymentLink {
        return DB::transaction(function () use (
            $brand,
            $clientData,
            $sellType,
            $serviceName,
            $currency,
            $totalCents,
            $payNowCents,
            $provider,
            $expiresInHours,
            $generatedBy
        ) {
            $this->assertValidBrand($brand);
            $this->assertAmountsForOriginal($totalCents, $payNowCents);

            $email = strtolower(trim((string) ($clientData['email'] ?? '')));
            $name = trim((string) ($clientData['name'] ?? 'Unknown'));
            $phone = $clientData['phone'] ?? null;

            if ($email === '') {
                throw new BusinessRuleException('Client email is required.');
            }

            $client = $this->findOrCreateClient(
                email: $email,
                name: $name,
                phone: $phone
            );

            $normalizedServiceName = $this->normalizeServiceName($serviceName);
            $normalizedCurrency = strtoupper(trim($currency));

            $order = UpworkOrder::query()
                ->where('client_id', $client->id)
                ->where('brand_id', $brand->id)
                ->where('service_name', $normalizedServiceName)
                ->where('order_type', 'original')
                ->where('balance_due', '>', 0)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                $order = UpworkOrder::create([
                    'module'        => ModuleType::UPWORK,
                    'client_id'     => $client->id,
                    'brand_id'      => $brand->id,
                    'order_type'    => 'original',
                    'sell_type'     => $sellType,
                    'service_name'  => $normalizedServiceName,
                    'currency'      => $normalizedCurrency,
                    'unit_amount'   => $totalCents,
                    'amount_paid'   => 0,
                    'balance_due'   => $totalCents,
                    'status'        => OrderStatus::PENDING,
                ]);
            } else {
                // $this->assertValidUpworkOrder($order);

                if ($order->currency !== $normalizedCurrency) {
                    throw new BusinessRuleException('Currency mismatch for existing open order.');
                }

                if ($totalCents > (int) $order->unit_amount) {
                    $order->unit_amount = $totalCents;
                    $order->balance_due = max(0, $totalCents - (int) $order->amount_paid);
                    $order->save();
                }
            }

            if ($payNowCents > (int) $order->balance_due) {
                throw new InvalidPaymentAmountException('Pay now exceeds remaining balance.');
            }

            $this->expireActiveLinksForOrder($order->id);

            $link = UpworkPaymentLink::create([
                'module'               => ModuleType::UPWORK,
                'order_id'             => $order->id,
                'brand_id'             => $brand->id,
                'client_id'            => $client->id,
                'service_name'         => $order->service_name,
                'currency'             => $order->currency,
                'provider'             => $provider,
                'unit_amount'          => $payNowCents,
                'order_total_snapshot' => (int) $order->unit_amount,
                'token'                => $this->generateUniqueToken(),
                'status'               => LinkStatus::ACTIVE,
                'expires_at'           => now()->addHours($this->capHours($expiresInHours)),
                'is_active_link'       => true,
                'generated_by_id'      => $generatedBy?->id,
                'generated_by_type'    => $generatedBy ? get_class($generatedBy) : null,
            ]);

            Log::info('Original Upwork payment link created', [
                'module' => ModuleType::UPWORK,
                'link_id' => $link->id,
                'order_id' => $order->id,
                'client_id' => $client->id,
                'brand_id' => $brand->id,
                'provider' => $provider,
                'generated_by_id' => $generatedBy?->id,
            ]);

            return $link;
        });
    }

    public function createInstallmentLinkForOrder(
        UpworkOrder $order,
        int $payNowCents,
        string $provider,
        int $expiresInHours = 168,
        $generatedBy = null
    ): UpworkPaymentLink {
        return DB::transaction(function () use ($order, $payNowCents, $provider, $expiresInHours, $generatedBy) {
            $lockedOrder = UpworkOrder::lockForUpdate()->findOrFail($order->id);

            // $this->assertValidUpworkOrder($lockedOrder);

            if ((int) $lockedOrder->balance_due <= 0 || $lockedOrder->status === OrderStatus::PAID) {
                throw new OrderAlreadyPaidException('Order is already paid.');
            }

            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                throw new BusinessRuleException('Cannot generate a payment link for a cancelled order.');
            }

            if ($payNowCents <= 0) {
                throw new InvalidPaymentAmountException('Invalid amount.');
            }

            if ($payNowCents > (int) $lockedOrder->balance_due) {
                throw new InvalidPaymentAmountException('Pay now exceeds remaining due.');
            }

            $this->expireActiveLinksForOrder($lockedOrder->id);

            $link = UpworkPaymentLink::create([
                'module'               => ModuleType::UPWORK,
                'order_id'             => $lockedOrder->id,
                'brand_id'             => $lockedOrder->brand_id,
                'client_id'            => $lockedOrder->client_id,
                'service_name'         => $lockedOrder->service_name,
                'currency'             => $lockedOrder->currency,
                'provider'             => $provider,
                'unit_amount'          => $payNowCents,
                'order_total_snapshot' => (int) $lockedOrder->unit_amount,
                'token'                => $this->generateUniqueToken(),
                'status'               => LinkStatus::ACTIVE,
                'expires_at'           => now()->addHours($this->capHours($expiresInHours)),
                'is_active_link'       => true,
                'generated_by_id'      => $generatedBy?->id,
                'generated_by_type'    => $generatedBy ? get_class($generatedBy) : null,
            ]);

            Log::info('Installment Upwork payment link created', [
                'module' => ModuleType::UPWORK,
                'link_id' => $link->id,
                'order_id' => $lockedOrder->id,
                'client_id' => $lockedOrder->client_id,
                'provider' => $provider,
                'generated_by_id' => $generatedBy?->id,
            ]);

            return $link;
        });
    }

    private function findOrCreateClient(string $email, string $name, ?string $phone): UpworkClient
    {
        try {
            return UpworkClient::create([
                // 'module' => ModuleType::UPWORK,
                'name'   => $name,
                'email'  => $email,
                'phone'  => $phone,
                'status' => 'Active',
            ]);
        } catch (QueryException $e) {
            $client = UpworkClient::where('email', $email)
                ->lockForUpdate()
                ->first();

            if (!$client) {
                throw $e;
            }

            // if (($client->module ?? null) !== ModuleType::UPWORK) {
            //     throw new ModuleMismatchException('Existing client does not belong to Upwork module.');
            // }

            return $client;
        }
    }

    private function expireActiveLinksForOrder(int $orderId): void
    {
        UpworkPaymentLink::where('order_id', $orderId)
            ->where('is_active_link', true)
            ->where('status', LinkStatus::ACTIVE)
            ->update([
                'is_active_link' => false,
                'status'         => LinkStatus::EXPIRED,
                'expires_at'     => now(),
            ]);
    }

    private function assertValidBrand(Brand $brand): void
    {
        if (($brand->module ?? null) !== ModuleType::UPWORK) {
            throw new ModuleMismatchException('Brand does not belong to Upwork module.');
        }
    }

    private function assertValidUpworkOrder(UpworkOrder $order): void
    {
        if (($order->module ?? null) !== ModuleType::UPWORK) {
            throw new ModuleMismatchException('Order does not belong to Upwork module.');
        }
    }

    private function assertAmountsForOriginal(int $totalCents, int $payNowCents): void
    {
        if ($totalCents <= 0) {
            throw new InvalidPaymentAmountException('Invalid total amount.');
        }

        if ($payNowCents <= 0) {
            throw new InvalidPaymentAmountException('Invalid pay now amount.');
        }

        if ($payNowCents > $totalCents) {
            throw new InvalidPaymentAmountException('Pay now exceeds total amount.');
        }
    }

    private function capHours(int $hours): int
    {
        return max(1, min(720, $hours));
    }

    private function normalizeServiceName(string $serviceName): string
    {
        $s = trim($serviceName);
        return preg_replace('/\s+/', ' ', $s) ?: $s;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (UpworkPaymentLink::where('token', $token)->exists());

        return $token;
    }
}
// class UpworkLinkGenerator
// {
//     /**
//      * Create an original order and its first payment link
//      */
//     public function createOriginalOrderAndFirstLink(
//         Brand $brand,
//         array $clientData,
//         string $sellType,
//         string $serviceName,
//         string $currency,
//         int $totalCents,
//         int $payNowCents,
//         string $provider,
//         int $expiresInHours = 168,
//         $generatedBy = null
//     ): UpworkPaymentLink {
//         return DB::transaction(function () use (
//             $brand,
//             $clientData,
//             $sellType,
//             $serviceName,
//             $currency,
//             $totalCents,
//             $payNowCents,
//             $provider,
//             $expiresInHours,
//             $generatedBy
//         ) {
//             try {
//                 // Guard against invalid amounts
//                 abort_unless($totalCents > 0, 422, 'Invalid total amount.');
//                 abort_unless($payNowCents > 0, 422, 'Invalid pay now amount.');
//                 abort_unless($payNowCents <= $totalCents, 422, 'Pay now exceeds total amount.');

//                 // 1) Client creation or reuse
//                 $email = strtolower(trim($clientData['email'] ?? ''));
//                 abort_unless($email, 422, 'Client email required.');

//                 try {
//                     $client = UpworkClient::create([
//                         'name'   => $clientData['name'] ?? 'Unknown',
//                         'email'  => $email,
//                         'phone'  => $clientData['phone'] ?? null,
//                         'status' => 'Active',
//                     ]);
//                 } catch (\Illuminate\Database\QueryException $e) {
//                     // If another request created it first, fetch it
//                     $client = UpworkClient::whereRaw('LOWER(email)=?', [$email])
//                         ->lockForUpdate()
//                         ->firstOrFail();
//                 }

//                 Log::info('Upwork client created/reused', ['client' => $client->toArray()]);

//                 // dd($client);

//                 // 2) Check for existing unpaid orders
//                 $serviceName = $this->normalizeServiceName($serviceName);

//                 $order = UpworkOrder::query()
//                     ->where('client_id', $client->id)
//                     ->where('brand_id', $brand->id)
//                     ->where('service_name', $serviceName)
//                     ->where('order_type', 'original')
//                     ->where('balance_due', '>', 0)
//                     ->lockForUpdate()
//                     ->first();

//                 if (!$order) {
//                     // Create a new original order if no existing unpaid order
//                     $order = UpworkOrder::create([
//                         'client_id'    => $client->id,
//                         'brand_id'     => $brand->id,
//                         'order_type'   => 'original',
//                         'sell_type'    => $sellType,
//                         'service_name' => $serviceName,
//                         'currency'     => strtoupper($currency),
//                         'unit_amount'  => $totalCents,
//                         'amount_paid'  => 0,
//                         'balance_due'  => $totalCents,
//                         'status'       => 'pending',
//                     ]);
//                 } else {
//                     // Reuse existing open order
//                     abort_unless($order->currency === strtoupper($currency), 422, 'Currency mismatch.');
//                     if ($totalCents > (int)$order->unit_amount) {
//                         $order->unit_amount = $totalCents;
//                         $order->balance_due = max(0, $totalCents - (int)$order->amount_paid);
//                         $order->save();
//                     }
//                 }

//                 // Ensure payNowCents does not exceed balance due
//                 abort_unless($payNowCents <= (int)$order->balance_due, 422, 'Pay now exceeds remaining balance.');

//                 // 3) Expire any existing active links for this order
//                 $this->expireActiveLinksForOrder($order->id);

//                 // 4) Create the payment link
//                 return UpworkPaymentLink::create([
//                     'order_id'             => $order->id,
//                     'brand_id'             => $brand->id,
//                     'client_id'            => $client->id,
//                     'service_name'         => $order->service_name,
//                     'currency'             => $order->currency,
//                     'provider'             => $provider,
//                     'unit_amount'          => $payNowCents,
//                     'order_total_snapshot' => (int)$order->unit_amount,
//                     'token'                => Str::random(48),
//                     'status'               => 'active',
//                     'expires_at'           => now()->addHours($this->capHours($expiresInHours)),
//                     'is_active_link'       => true,
//                     'generated_by_id'      => $generatedBy?->id,
//                     'generated_by_type'    => $generatedBy ? class_basename($generatedBy) : null,
//                 ]);
//             } catch (\Exception $e) {
//                 Log::error('Error generating original order and first payment link', ['error' => $e->getMessage()]);
//                 throw new \RuntimeException('An error occurred while processing the payment link generation.');
//             }
//         });
//     }

//     /**
//      * Create an installment link for a given order
//      */
//     public function createInstallmentLinkForOrder(
//         UpworkOrder $order,
//         int $payNowCents,
//         string $provider,
//         int $expiresInHours = 168,
//         $generatedBy = null
//     ): UpworkPaymentLink {
//         return DB::transaction(function () use ($order, $payNowCents, $provider, $expiresInHours, $generatedBy) {

//             try {
//                 $order = UpworkOrder::lockForUpdate()->findOrFail($order->id);

//                 abort_unless((int)$order->balance_due > 0, 422, 'Order already paid.');
//                 abort_unless($payNowCents > 0, 422, 'Invalid amount.');
//                 abort_unless($payNowCents <= (int)$order->balance_due, 422, 'Pay now exceeds remaining due.');

//                 // 3) Expire any active links for this order
//                 $this->expireActiveLinksForOrder($order->id);

//                 // 4) Create installment payment link
//                 return UpworkPaymentLink::create([
//                     'order_id'             => $order->id,
//                     'brand_id'             => $order->brand_id,
//                     'client_id'            => $order->client_id,
//                     'service_name'         => $order->service_name,
//                     'currency'             => $order->currency,
//                     'provider'             => $provider,
//                     'unit_amount'          => $payNowCents,
//                     'order_total_snapshot' => (int)$order->unit_amount,
//                     'token'                => Str::random(48),
//                     'status'               => 'active',
//                     'expires_at'           => now()->addHours($this->capHours($expiresInHours)),
//                     'is_active_link'       => true,
//                     'generated_by_id'      => $generatedBy?->id,
//                     'generated_by_type'    => $generatedBy ? class_basename($generatedBy) : null,
//                 ]);
//             } catch (\Exception $e) {
//                 Log::error('Error generating installment payment link', ['error' => $e->getMessage()]);
//                 throw new \RuntimeException('An error occurred while processing the installment payment link generation.');
//             }
//         });
//     }

//     /**
//      * Expire any active payment links for a given order.
//      */
//     private function expireActiveLinksForOrder(int $orderId): void
//     {
//         UpworkPaymentLink::where('order_id', $orderId)
//             ->where('is_active_link', true)
//             ->update([
//                 'is_active_link' => false,
//                 'status'         => 'expired',
//                 'expires_at'     => now(),
//             ]);
//     }

//     /**
//      * Ensure the expiration hours are within acceptable bounds.
//      */
//     private function capHours(int $hours): int
//     {
//         return max(1, min(720, $hours));
//     }

//     /**
//      * Normalize the service name (trim spaces, collapse multiple spaces).
//      */
//     private function normalizeServiceName(string $serviceName): string
//     {
//         $s = trim($serviceName);
//         $s = preg_replace('/\s+/', ' ', $s); // collapse multiple spaces
//         return $s;
//     }
// }
