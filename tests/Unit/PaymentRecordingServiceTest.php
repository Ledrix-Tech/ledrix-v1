<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Services\BriefService;
use App\Services\Payments\PaymentRecordOutcome;
use App\Services\Payments\PaymentRecordingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\TestCase;

class PaymentRecordingServiceTest extends TestCase
{
    use CreatesPaymentFlow;
    use RefreshDatabase;

    private PaymentRecordingService $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(BriefService::class, function ($mock) {
            $mock->shouldReceive('dispatchBriefEmail')->andReturnNull();
        });

        $this->recorder = app(PaymentRecordingService::class);
    }

    public function test_records_stripe_payment_and_marks_order_paid(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::create([
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Web Design',
            'currency'        => 'USD',
            'unit_amount'     => 50_000,
            'amount_paid'     => 0,
            'status'          => 'draft',
            'order_type'      => 'original',
        ]);

        $link = PaymentLink::create([
            'lead_id'        => $lead->id,
            'brand_id'       => $brand->id,
            'client_id'      => $client->id,
            'order_id'       => $order->id,
            'seller_id'      => $seller->id,
            'service_name'   => 'Web Design',
            'currency'       => 'USD',
            'unit_amount'    => 50_000,
            'token'          => 'test-token-' . uniqid(),
            'status'         => 'active',
            'is_active_link' => true,
            'provider'       => 'stripe',
            'expires_at'     => now()->addDay(),
        ]);

        [$payment, $paidOrder, $isNew] = $this->recorder->record(
            link: $link,
            provider: 'stripe',
            providerTxnId: 'pi_test_success_001',
            reportedAmountCents: 50_000,
            reportedCurrency: 'USD',
            payload: ['id' => 'pi_test_success_001'],
            sessionId: 'cs_test_001',
        );

        $this->assertTrue($isNew);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('stripe', $payment->provider);

        $paidOrder->refresh();
        $link->refresh();

        $this->assertSame(50_000, (int) $paidOrder->amount_paid);
        $this->assertSame('paid', $paidOrder->status);
        $this->assertSame(0, (int) $paidOrder->balance_due);
        $this->assertSame('paid', $link->status);
        $this->assertFalse((bool) $link->is_active_link);
    }

    public function test_duplicate_transaction_is_idempotent(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::create([
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Logo Design',
            'currency'        => 'USD',
            'unit_amount'     => 10_000,
            'amount_paid'     => 0,
            'status'          => 'draft',
            'order_type'      => 'original',
        ]);

        $link = PaymentLink::create([
            'lead_id'        => $lead->id,
            'brand_id'       => $brand->id,
            'client_id'      => $client->id,
            'order_id'       => $order->id,
            'seller_id'      => $seller->id,
            'service_name'   => 'Logo Design',
            'currency'       => 'USD',
            'unit_amount'    => 10_000,
            'token'          => 'test-token-' . uniqid(),
            'status'         => 'active',
            'is_active_link' => true,
            'provider'       => 'stripe',
            'expires_at'     => now()->addDay(),
        ]);

        $args = [
            'link'                  => $link,
            'provider'              => 'stripe',
            'providerTxnId'         => 'pi_duplicate_001',
            'reportedAmountCents'   => 10_000,
            'reportedCurrency'      => 'USD',
            'payload'               => ['id' => 'pi_duplicate_001'],
        ];

        [, , $first] = $this->recorder->record(...$args);
        [, , $second] = $this->recorder->record(...$args);

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, Payment::count());
    }

    public function test_marks_lead_as_first_paid_after_successful_payment(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph([
            'lead' => ['status' => 'qualified'],
        ]);

        $order = Order::create([
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'SEO Package',
            'currency'        => 'USD',
            'unit_amount'     => 25_000,
            'amount_paid'     => 0,
            'status'          => 'draft',
            'order_type'      => 'original',
        ]);

        $link = PaymentLink::create([
            'lead_id'        => $lead->id,
            'brand_id'       => $brand->id,
            'client_id'      => $client->id,
            'order_id'       => $order->id,
            'seller_id'      => $seller->id,
            'service_name'   => 'SEO Package',
            'currency'       => 'USD',
            'unit_amount'    => 25_000,
            'token'          => 'test-token-' . uniqid(),
            'status'         => 'active',
            'is_active_link' => true,
            'provider'       => 'paypal',
            'expires_at'     => now()->addDay(),
        ]);

        $this->recorder->record(
            link: $link,
            provider: 'paypal',
            providerTxnId: 'CAPTURE_TEST_001',
            reportedAmountCents: 25_000,
            reportedCurrency: 'USD',
            payload: ['id' => 'CAPTURE_TEST_001'],
        );

        $lead->refresh();

        $this->assertSame('first_paid', $lead->status);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_webhook_wrapper_returns_duplicate_outcome_for_repeat_event(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::create([
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Audit',
            'currency'        => 'USD',
            'unit_amount'     => 5_000,
            'amount_paid'     => 0,
            'status'          => 'draft',
            'order_type'      => 'original',
        ]);

        $link = PaymentLink::create([
            'lead_id'        => $lead->id,
            'brand_id'       => $brand->id,
            'client_id'      => $client->id,
            'order_id'       => $order->id,
            'seller_id'      => $seller->id,
            'service_name'   => 'Audit',
            'currency'       => 'USD',
            'unit_amount'    => 5_000,
            'token'          => 'test-token-' . uniqid(),
            'status'         => 'active',
            'is_active_link' => true,
            'provider'       => 'stripe',
            'expires_at'     => now()->addDay(),
        ]);

        $payload = [
            'link'                  => $link,
            'provider'              => 'stripe',
            'providerTxnId'         => 'pi_webhook_dup',
            'reportedAmountCents'   => 5_000,
            'reportedCurrency'      => 'USD',
            'payload'               => [],
        ];

        $first = $this->recorder->recordFromWebhook(...$payload);
        $second = $this->recorder->recordFromWebhook(...$payload);

        $this->assertTrue($first->isNew);
        $this->assertSame(PaymentRecordOutcome::OK_NEW, $first->status);
        $this->assertSame(PaymentRecordOutcome::OK_DUPLICATE, $second->status);
        $this->assertFalse($second->isNew);
    }
}
