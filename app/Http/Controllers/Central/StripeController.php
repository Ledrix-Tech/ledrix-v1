<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantLimit;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use App\Models\Central\TenantRenewalRequest;
use App\Notifications\MembershipInvoice;
use App\Notifications\RenewalApproval;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function sendRenewalApproval(Tenant $tenant)
    {
        if (! $tenant->stripe_customer_id || ! $tenant->stripe_payment_method_id) {
            return back()->with(
                'error',
                'This tenant has no saved Stripe card for off-session renewal. Ask them to renew via Organization Billing (Checkout), or confirm a bank/PayFast payment instead.'
            );
        }

        if (! $tenant->plan_id) {
            return back()->with('error', 'This tenant has no plan assigned.');
        }

        $token = Str::random(40);

        $renew = TenantRenewalRequest::create([
            'tenant_id'  => $tenant->id,
            'plan_id'    => $tenant->plan_id,
            'token'      => $token,
            'expires_at' => now()->addHours(24),
            'status'     => 'pending',
        ]);

        $tenant->notify(
            (new RenewalApproval($tenant, $renew))
                ->delay(now()->addSeconds(2))
        );

        return back()->with('success', 'Approval email sent to tenant.');
    }

    public function approveRenewal($token)
    {
        $requestRecord = TenantRenewalRequest::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('status', 'pending')
            ->first();

        if (! $requestRecord) {
            return view('renewal-approved', [
                'company' => null,
                'status'  => 'expired',
                'message' => 'Approval link has expired.',
            ]);
        }

        $requestRecord->update(['status' => 'approved']);
        $company = Tenant::find($requestRecord->tenant_id);

        try {
            $this->processRenewal($company);
            $status  = 'success';
            $message = 'Your subscription has been renewed successfully!';
        } catch (\Throwable $e) {
            Log::error('Auto-renewal failed: ' . $e->getMessage());
            $status  = 'failed';
            $message = 'Auto-renewal failed. Please contact support or try again from your tenant portal.';
        }

        return view('renewal-approved', compact('company', 'status', 'message'));
    }

    public function processRenewal(Tenant $company, string $renewedBy = 'tenant_approval'): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $package = PackagePricing::find($company->plan_id);
        if (! $package) {
            throw new \RuntimeException('Invalid package selected.');
        }

        if (! $company->stripe_customer_id || ! $company->stripe_payment_method_id) {
            throw new \RuntimeException('No saved Stripe payment method found for this tenant.');
        }

        $amount = (int) ($package->monthly_price * 100);

        try {
            $paymentIntent = PaymentIntent::create([
                'customer'       => $company->stripe_customer_id,
                'amount'         => $amount,
                'currency'       => strtolower($package->currency ?? 'usd'),
                'payment_method' => $company->stripe_payment_method_id,
                'off_session'    => true,
                'confirm'        => true,
                'description'    => 'Subscription renewal for ' . $company->name,
                'metadata'       => [
                    'tenant_id'  => $company->id,
                    'plan_id'    => $package->id,
                    'renewed_by' => $renewedBy,
                ],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            throw new \RuntimeException('Payment declined: ' . ($e->getError()->message ?? $e->getMessage()), 0, $e);
        }

        if ($paymentIntent->status !== 'succeeded') {
            throw new \RuntimeException('Payment failed. Please try again.');
        }

        $payment = TenantPayment::create([
            'tenant_id'      => $company->id,
            'gateway'        => 'stripe',
            'transaction_id' => $paymentIntent->id,
            'plan_id'        => $package->id,
            'amount'         => $amount / 100,
            'currency'       => strtoupper($package->currency ?? 'USD'),
            'status'         => 'paid',
            'order_type'     => 'renewal',
            'renewed_by'     => $renewedBy,
            'paid_at'        => now(),
        ]);

        $existingMembership = TenantMembership::where('tenant_id', $company->id)
            ->where('status', 'active')
            ->latest('end_date')
            ->first();

        if ($existingMembership && $existingMembership->end_date && $existingMembership->end_date->isFuture()) {
            $existingMembership->update([
                'end_date'   => Carbon::parse($existingMembership->end_date)->addMonth(),
                'amount'     => $amount / 100,
                'renewed_by' => $renewedBy,
            ]);
            $membership = $existingMembership;
        } else {
            $membership = TenantMembership::create([
                'tenant_id'     => $company->id,
                'plan_id'       => $package->id,
                'billing_cycle' => 'monthly',
                'api_key'       => Str::random(60),
                'start_date'    => now()->toDateString(),
                'end_date'      => now()->addMonth()->toDateString(),
                'amount'        => $amount / 100,
                'currency'      => strtoupper($package->currency ?? 'USD'),
                'status'        => 'active',
                'renewed_by'    => $renewedBy,
            ]);
        }

        $payment->update(['membership_id' => $membership->id]);
        $company->update(['status' => 'active']);

        TenantLimit::updateOrCreate(
            ['tenant_id' => $company->id],
            $this->limitsFromPackage($package)
        );

        Log::info('Tenant renewed successfully via approval link', [
            'tenant_id'      => $company->id,
            'transaction_id' => $paymentIntent->id,
            'amount'         => $amount,
        ]);

        $company->notify(
            (new MembershipInvoice($company, $payment))
                ->delay(now()->addSeconds(5))
        );
    }

    public function startCheckout(Request $request, Tenant $company)
    {
        if (! $company->stripe_customer_id || ! $company->stripe_payment_method_id) {
            return back()->with(
                'error',
                'No saved Stripe card for off-session charge. Use Organization Billing Checkout, or confirm another payment method.'
            );
        }

        try {
            $this->processRenewal($company, 'super_admin');
        } catch (\Throwable $e) {
            Log::error('Stripe Renewal Error: ' . $e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        $membership = TenantMembership::where('tenant_id', $company->id)
            ->where('status', 'active')
            ->latest('end_date')
            ->first();

        return view('front.pages.checkout-success', compact('company', 'membership'))
            ->with('success', 'Tenant subscription renewed successfully.');
    }

    public function cancel(Tenant $company)
    {
        return view('front.pages.checkout-cancel', compact('company'));
    }

    private function limitsFromPackage(PackagePricing $package): array
    {
        return [
            'package_id'        => $package->id,
            'max_admins'        => $package->max_admins,
            'max_users'         => $package->max_sellers,
            'max_brands'        => $package->max_brands,
            'max_sellers'       => $package->max_sellers,
            'max_clients'       => $package->max_clients,
            'max_leads'         => $package->max_leads_per_month,
            'max_orders'        => $package->max_orders,
            'max_payment_links' => $package->max_payment_links,
            'max_projects'      => $package->max_projects,
            'max_storage_mb'    => $package->max_storage_mb,
        ];
    }
}
