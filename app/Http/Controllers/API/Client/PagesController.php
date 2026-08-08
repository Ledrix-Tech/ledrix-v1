<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Order;
use App\Models\ProfileDetail;
use App\Support\ClientPortalAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PagesController extends Controller
{
    public function clientIndex()
    {
        $client = auth('client')->user();

        $statuses = [
            'draft',
            'pending',
            'paid',
            'in_progress',
            'revision',
            'completed',
            'refunded',
            'canceled',
        ];

        $counts = Order::query()
            ->where('client_id', $client->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $chartData = collect($statuses)->map(function ($status) use ($counts) {
            return [
                'status' => ucfirst(str_replace('_', ' ', $status)),
                'count' => (int) ($counts[$status] ?? 0),
            ];
        });

        return view('clients.pages.dashboard', compact('client', 'chartData'));
    }

    

    public function clientMessages()
    {
        return view('clients.pages.messages');
    }

    public function clientInvoices(Request $request)
    {
        $client = auth('client')->user();

        $query = Order::query()
            ->with(['brand:id,brand_name', 'seller:id,name', 'client:id,name,email'])
            ->where('client_id', $client->id);

        if ($request->filled('package')) {
            $query->where('service_name', 'like', '%' . trim($request->string('package')) . '%');
        }

        if ($request->filled('invoice')) {
            $invoiceId = (int) preg_replace('/\D/', '', (string) $request->invoice);
            if ($invoiceId > 0) {
                $query->where('id', $invoiceId);
            }
        }

        if ($request->filled('status')) {
            $status = (string) $request->status;
            $allowed = ['draft', 'pending', 'paid', 'in_progress', 'revision', 'completed', 'refunded', 'canceled'];
            if (in_array($status, $allowed, true)) {
                $query->where('status', $status);
            }
        }

        $orders = $query->latest('id')->paginate(20)->withQueryString();

        return view('clients.pages.invoices', compact('orders', 'client'));
    }

    public function clientInvoiceDetails(Order $order)
    {
        $client = ClientPortalAuthorization::client();
        ClientPortalAuthorization::assertOwnsOrder($order);

        $order->load([
            'brand:id,brand_name',
            'seller:id,name',
            'client:id,name,email',
            'paymentLinks:id,order_id,unit_amount,status,paid_at,token,last_issued_url,last_issued_at,expires_at,is_active_link,currency',
            'payments:id,order_id,amount,currency,status,created_at',
        ]);

        // Must match checkout: is_active_link + unpaid + not expired (PaymentLink::isActiveLink).
        $latestActiveLink = $order->paymentLinks()
            ->where('is_active_link', true)
            ->where('status', '!=', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('last_issued_at')
            ->orderByDesc('id')
            ->first();

        $payUrl = null;
        if ($latestActiveLink && $latestActiveLink->isActiveLink()) {
            $payUrl = filled($latestActiveLink->last_issued_url)
                ? $latestActiveLink->last_issued_url
                : $latestActiveLink->signedUrl();
        } else {
            $latestActiveLink = null;
        }

        return view('clients.pages.invoice-details', compact('order', 'client', 'latestActiveLink', 'payUrl'));
    }

    public function clientProfile()
    {
        $user = Auth::guard('admin')->user()
            ?? Auth::guard('seller')->user()
            ?? Auth::guard('client')->user();
        if (!$user) {
            abort(403, 'No user logged in');
        }
        // Check if profile details exist
        $profile = ProfileDetail::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->first();

        // Split name into first/last
        $fullName = $profile->name ?? $user->name;
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';
        // dd($user);
        // return response()->json([
        //     'status'  => true,
        //     'message' => 'Client orders fetched successfully',
        //     'data'    => [
        //         'user' => $user,
        //         'profile' => $profile,
        //         'firstName' => $firstName,
        //         'lastName' => $lastName
        //     ],
        // ]);
        return view('clients.pages.profile', compact('user', 'profile', 'firstName', 'lastName'));
    }
}
