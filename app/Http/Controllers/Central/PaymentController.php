<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    // Step 4: Initiate JazzCash Payment
    public function initiatePayment(Request $request)
    {
        $amounts = [
            'leads-classify' => 2000,
            'churn-predict' => 3000,
            'future-revenue' => 4000,
        ];

        $company = Tenant::findOrFail(session('company_id'));
        $plan = session('plan');
        $amount = $amounts[$plan] ?? 1000;

        $pp_TxnRefNo = 'T' . time();
        $fields = [
            "pp_Version" => "1.1",
            "pp_TxnType" => "MWALLET",
            "pp_Language" => "EN",
            "pp_MerchantID" => env('JAZZCASH_MERCHANT_ID'),
            "pp_Password" => env('JAZZCASH_PASSWORD'),
            "pp_TxnRefNo" => $pp_TxnRefNo,
            "pp_Amount" => $amount * 100,
            "pp_TxnCurrency" => "PKR",
            "pp_ReturnURL" => env('JAZZCASH_RETURN_URL'),
            "pp_TxnDateTime" => now()->format('YmdHis'),
            "pp_BillReference" => $plan,
            "pp_Description" => "CRM Subscription - $plan",
        ];

        // Generate Secure Hash
        $sortedString = implode('&', $fields) . '&' . env('JAZZCASH_SALT');
        $fields["pp_SecureHash"] = hash_hmac('sha256', $sortedString, env('JAZZCASH_SALT'));

        return view('jazzcash.checkout', compact('fields'));
    }

    // Step 5: Handle JazzCash callback
    public function paymentCallback(Request $request)
    {
        $company = Tenant::find(session('company_id'));

        if ($request->pp_ResponseCode == '000') {
            $company->update([
                'status' => 'active',
                'subscription_start' => now(),
                'subscription_end' => now()->addMonth(),
            ]);

            $company->featureFlags()->create([
                'feature_key'   => $company->plan?->slug ?? 'default',
                'is_enabled'    => true,
                'enabled_from'  => now()->toDateString(),
                'enabled_until' => now()->addMonth()->toDateString(),
            ]);

            return redirect()->route('plans.show')->with('success', 'Payment successful! Subscription activated.');
        }

        return redirect()->route('plans.show')->with('error', 'Payment failed.');
    }

    public function checkout(Tenant $company)
    {
        // $plan = CompanyFeature::where('name', $company->plan)->firstOrFail();
        $plan = $company->plan;
        // Here you would create a Payoneer Checkout Session
        // Using GuzzleHttp for API call
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.sandbox.payoneer.com/v4/programs/{program_id}/payouts', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYONEER_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'payee_id' => $company->email, // simplified mapping
                'amount'   => $plan->price,
                'currency' => 'USD',
                'description' => "Subscription for {$plan->name}",
                'redirect_url' => route('payoneer.webhook'),
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        return redirect($data['checkout_url']); // Payoneer’s URL
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Payoneer webhook:', $request->all());

        $email = $request->input('payee_id');
        $company = Tenant::where('email', $email)->first();

        if ($request->input('status') === 'success' && $company) {
            $company->update([
                'status' => 'active',
                'subscription_start' => now(),
                'subscription_end'   => now()->addMonth(),
            ]);

            $company->featureFlags()->create([
                'feature_key'   => $company->plan?->slug ?? 'default',
                'is_enabled'    => true,
                'enabled_from'  => now()->toDateString(),
                'enabled_until' => now()->addMonth()->toDateString(),
            ]);
        }

        return response()->json(['message' => 'Webhook processed']);
    }


    // public function start($companyId)
    // {
    //     $company = Tenant::findOrFail($companyId);

    //     $txnRef = Str::random(12);
    //     $amount = 1000; // Example: set price based on plan
    //     $plan   = $company->plan;

    //     // Save payment entry
    //     $payment = Payment::create([
    //         'company_id'     => $company->id,
    //         'transaction_id' => $txnRef,
    //         'order_id'       => Str::uuid(),
    //         'amount'         => $amount,
    //         'plan'           => $plan,
    //         'status'         => 'pending',
    //     ]);

    //     $postData = [
    //         "pp_Version" => "1.1",
    //         "pp_TxnType" => "MWALLET",
    //         "pp_Language" => "EN",
    //         "pp_MerchantID" => env('JAZZCASH_MERCHANT_ID'),
    //         "pp_SubMerchantID" => "",
    //         "pp_Password" => env('JAZZCASH_PASSWORD'),
    //         "pp_BankID" => "TBANK",
    //         "pp_ProductID" => "RETL",
    //         "pp_TxnRefNo" => $txnRef,
    //         "pp_Amount" => $amount * 100, // JazzCash expects *100
    //         "pp_TxnCurrency" => "PKR",
    //         "pp_TxnDateTime" => now()->format('YmdHis'),
    //         "pp_BillReference" => "billRef",
    //         "pp_Description" => "Subscription for {$plan}",
    //         "pp_ReturnURL" => env('JAZZCASH_RETURN_URL'),
    //     ];

    //     $postData["pp_SecureHash"] = JazzCash::generateHash($postData, env('JAZZCASH_INTEGRITY_SALT'));

    //     return view('checkout.jazzcash', compact('postData'));
    // }

    // public function response(Request $request)
    // {
    //     $response = $request->all();

    //     $payment = Payment::where('transaction_id', $response['pp_TxnRefNo'])->firstOrFail();

    //     if ($response['pp_ResponseCode'] === '000') {
    //         // Success
    //         $payment->update([
    //             'status' => 'success',
    //             'payload' => json_encode($response),
    //         ]);

    //         $company = $payment->company;
    //         $company->update([
    //             'status' => 'active',
    //             'subscription_start' => now(),
    //             'subscription_end' => now()->addMonth(),
    //         ]);

    //         PremiumFeature::updateOrCreate(
    //             ['company_id' => $company->id, 'feature' => $payment->plan],
    //             [
    //                 'status' => 'active',
    //                 'started_at' => now(),
    //                 'expires_at' => now()->addMonth(),
    //             ]
    //         );

    //         return redirect()->route('dashboard')->with('success', 'Payment successful, subscription activated.');
    //     } else {
    //         $payment->update([
    //             'status' => 'failed',
    //             'payload' => json_encode($response),
    //         ]);

    //         return redirect()->route('checkout.failed')->with('error', 'Payment failed.');
    //     }
    // }
}
