<?php

namespace App\Http\Controllers\Seller;

use App\Models\Lead;
use Illuminate\Http\Request;
use App\Services\StripeGateway;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{

    public function sellerLeadFinish(Lead $lead)
    {
        PortalAuthorization::requirePortalActor();

        if (! auth('admin')->check()) {
            $seller = auth('seller')->user();
            Gate::forUser($seller)->authorize('finish', $lead);
        }
        // require payment before finishing (optional but sensible)
        $paid = $lead->paymentLinks()->where('status', 'paid')->exists()
            || optional($lead->client)->orders()->where('status', 'paid')->exists();
        if (!$paid) {
            return back()->with('error', 'Cannot mark as finished until payment is received.');
        }
        $next = ! (bool) $lead->is_finish;
        $lead->update([
            'is_finish' => $next,
        ]);

        return back()->with('success', $next ? 'Lead marked as finished.' : 'Lead reopened.');
    }


    // public function checkoutSuccess(Request $request, string $token, StripeGateway $stripe)
    // {
    //     $link = PaymentLink::with('order')->where('token', $token)->firstOrFail();
    //     $sessionId = $request->query('session_id');
    //     $stripe->handleCheckoutSuccess($link, $sessionId);

    //     return view('paid-success', ['link' => $link->fresh('order'), 'order' => $link->order]);
    // }

    public function handle(Request $request, StripeGateway $stripe)
    {
        $ok = $stripe->handleWebhook($request->getContent(), $request->headers->all());
        return response()->json(['ok' => $ok]);
    }
}
