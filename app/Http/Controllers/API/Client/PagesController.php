<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Lead;
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

        // All possible lead statuses (enum list)
        $statuses = [
            'new',
            'contacted',
            'qualified',
            'proposal_sent',
            'first_paid',
            'in_progress',
            'completed',
            'renewal_due',
            'on_hold',
            'disqualified',
            'cancelled',
        ];

        // Count leads grouped by status for this client
        $counts = Lead::where('client_id', $client->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Prepare chart data ensuring all statuses exist
        $chartData = collect($statuses)->map(function ($status) use ($counts) {
            return [
                'status' => ucfirst(str_replace('_', ' ', $status)),
                'count' => $counts[$status] ?? 0,
            ];
        });

        // Quick debug check (uncomment if chart blank)
        // dd($chartData->toArray());

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
            if ($request->status === 'paid') {
                $query->where('status', 'paid');
            } elseif ($request->status === 'pending') {
                $query->where('status', '!=', 'paid');
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
            'paymentLinks:id,order_id,unit_amount,status,paid_at,token,last_issued_url',
            'payments:id,order_id,amount,currency,status,created_at',
        ]);

        // latest ACTIVE (unpaid + not expired) link for THIS order
        $latestActiveLink = $order->paymentLinks()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('last_issued_at')
            ->orderByDesc('id')
            ->first();

        // dd($order, $lastLink, $outstandingUrl);
        // return response()->json([
        //     'status'  => true,
        //     'message' => 'Client orders fetched successfully',
        //     'data'    => [
        //         'order' => $order,
        //         'client' => $client,
        //         'lastActiveLink' => $latestActiveLink
        //     ],
        // ]);

        return view('clients.pages.invoice-details', compact('order', 'client', 'latestActiveLink'));
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
