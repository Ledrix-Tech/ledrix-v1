<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Services\BriefService;
use App\Services\PaymentLinkService;
use App\Services\Payments\PaymentRecordOutcome;
use App\Services\Payments\PaymentRecordingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreatesPaymentFlow;
use Tests\TestCase;

/**
 * End-to-end payment path inside the app:
 * Lead → payment link (order created) → gateway record → paid order + converted lead.
 */
class PaymentLeadToSuccessTest extends TestCase
{
    use CreatesPaymentFlow;
    use RefreshDatabase;

    public function test_lead_to_payment_link_to_successful_stripe_payment(): void
    {
        Notification::fake();

        $this->mock(BriefService::class, function ($mock) {
            $mock->shouldReceive('dispatchBriefEmail')->once()->andReturnNull();
        });

        ['brand' => $brand, 'lead' => $lead, 'seller' => $seller] = $this->createPaymentLeadGraph([
            'lead' => [
                'status'  => 'qualified',
                'service' => 'Website Redesign',
            ],
        ]);

        $totalCents = 120_000;
        $payNowCents = 120_000;

        /** @var PaymentLinkService $linkService */
        $linkService = app(PaymentLinkService::class);

        $link = $linkService->createInstallmentLink(
            brand: $brand,
            lead: $lead,
            sellerIdWhoGenerated: $seller->id,
            serviceName: 'Website Redesign',
            currency: 'USD',
            totalCents: $totalCents,
            payNowCents: $payNowCents,
            provider: 'stripe',
            orderType: 'original',
        );

        $this->assertInstanceOf(PaymentLink::class, $link);
        $this->assertSame('active', $link->status);
        $this->assertTrue($link->is_active_link);
        $this->assertSame($payNowCents, (int) $link->unit_amount);

        $order = Order::findOrFail($link->order_id);
        $this->assertSame($lead->id, $order->lead_id);
        $this->assertSame($totalCents, (int) $order->unit_amount);
        $this->assertSame(0, (int) $order->amount_paid);

        /** @var PaymentRecordingService $recorder */
        $recorder = app(PaymentRecordingService::class);

        [$payment, $paidOrder, $isNew] = $recorder->record(
            link: $link,
            provider: 'stripe',
            providerTxnId: 'pi_flow_e2e_001',
            reportedAmountCents: $payNowCents,
            reportedCurrency: 'USD',
            payload: ['object' => 'payment_intent', 'id' => 'pi_flow_e2e_001'],
            sessionId: 'cs_flow_e2e_001',
        );

        $this->assertTrue($isNew);
        $this->assertSame('succeeded', $payment->status);

        $paidOrder->refresh();
        $link->refresh();
        $lead->refresh();

        $this->assertSame('paid', $paidOrder->status);
        $this->assertSame($payNowCents, (int) $paidOrder->amount_paid);
        $this->assertSame(0, (int) $paidOrder->balance_due);
        $this->assertSame('paid', $link->status);
        $this->assertFalse((bool) $link->is_active_link);
        $this->assertSame('first_paid', $lead->status);
        $this->assertNotNull($lead->converted_at);

        $this->assertDatabaseHas('payments', [
            'order_id'                   => $order->id,
            'payment_link_id'            => $link->id,
            'provider'                   => 'stripe',
            'provider_payment_intent_id' => 'pi_flow_e2e_001',
            'status'                     => 'succeeded',
            'amount'                     => $payNowCents,
        ]);
    }

    public function test_milestone_payment_leaves_order_pending_until_fully_paid(): void
    {
        Notification::fake();

        $this->mock(BriefService::class, function ($mock) {
            $mock->shouldReceive('dispatchBriefEmail')->never();
        });

        ['brand' => $brand, 'lead' => $lead, 'client' => $client, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::create([
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Branding Package',
            'currency'        => 'USD',
            'unit_amount'     => 100_000,
            'amount_paid'     => 0,
            'status'          => 'draft',
            'order_type'      => 'original',
        ]);

        $link = PaymentLink::create([
            'lead_id'              => $lead->id,
            'brand_id'             => $brand->id,
            'client_id'            => $client->id,
            'order_id'             => $order->id,
            'seller_id'            => $seller->id,
            'service_name'         => 'Branding Package',
            'currency'             => 'USD',
            'unit_amount'          => 40_000,
            'order_total_snapshot' => 100_000,
            'token'                => 'milestone-' . uniqid(),
            'status'               => 'active',
            'is_active_link'       => true,
            'provider'             => 'paypal',
            'expires_at'           => now()->addDay(),
        ]);

        app(PaymentRecordingService::class)->record(
            link: $link,
            provider: 'paypal',
            providerTxnId: 'capture_milestone_1',
            reportedAmountCents: 40_000,
            reportedCurrency: 'USD',
            payload: [],
        );

        $order->refresh();
        $lead->refresh();

        $this->assertSame('pending', $order->status);
        $this->assertSame(40_000, (int) $order->amount_paid);
        $this->assertSame(60_000, (int) $order->balance_due);
        $this->assertSame('first_paid', $lead->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_amount_mismatch_from_provider_is_rejected(): void
    {
        ['brand' => $brand, 'lead' => $lead, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $link = app(PaymentLinkService::class)->createInstallmentLink(
            brand: $brand,
            lead: $lead,
            sellerIdWhoGenerated: $seller->id,
            serviceName: 'Consulting',
            currency: 'USD',
            totalCents: 30_000,
            payNowCents: 30_000,
            provider: 'stripe',
        );

        $outcome = app(PaymentRecordingService::class)->recordFromWebhook(
            link: $link,
            provider: 'stripe',
            providerTxnId: 'pi_bad_amount',
            reportedAmountCents: 29_999,
            reportedCurrency: 'USD',
            payload: [],
        );

        $this->assertSame(PaymentRecordOutcome::SKIPPED, $outcome->status);
        $this->assertSame(0, Payment::count());
        $this->assertSame('active', $link->fresh()->status);
    }
}
