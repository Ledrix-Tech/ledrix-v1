<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Upwork\UpworkOrder;
use App\Models\Upwork\UpworkClient;
use App\Http\Controllers\Controller;

class UpworkController extends Controller
{
    public function upworkClients()
    {
        $clients = UpworkClient::paginate(20);

        return view('admin.pages.upwork-clients', [
            'clients' => $clients,
        ]);
    }

    // optimized logic
    public function upworkOrders(Request $request)
    {
        $isAdmin = auth('admin')->check();
        if (!$isAdmin) {
            return redirect()->route('upwork.login.get')->with('error', 'You must be logged in.');
        }

        $query = UpworkOrder::with([
            'brand:id,brand_name',
            'client:id,name,email',
            'latestPaymentLink:id,order_id,is_active_link,last_issued_url'
        ]);
        // --- Filters ---
        $query
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('brand_id'), fn($q) => $q->where('brand_id', (int) $request->brand_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->q);
                $q->where(
                    fn($w) =>
                    $w->where('service_name', 'like', "%{$term}%")
                        ->orWhere('buyer_name', 'like', "%{$term}%")
                        ->orWhere('buyer_email', 'like', "%{$term}%")
                );
            });

        $orders = $query->where('order_type', 'original')->paginate(20)->withQueryString();

        // dd($query,$orders);
        return view('admin.pages.upwork-orders', compact('orders'));
    }

    public function upworkPayments(Request $request)
    {
        $isAdmin = auth('admin')->check();
        if (!$isAdmin) {
            return redirect()->route('upwork.login.get')->with('error', 'You must be logged in.');
        }

        $query = UpworkOrder::with([
            'brand:id,brand_name',
            'client:id,name,email',
        ]);

        // optional filters
        if ($request->filled('status'))   $query->where('status', $request->string('status'));
        if ($request->filled('brand_id')) $query->where('brand_id', (int) $request->brand_id);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('service_name', 'like', "%{$q}%")
                    ->orWhere('buyer_name', 'like', "%{$q}%")
                    ->orWhere('buyer_email', 'like', "%{$q}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();
        return view('admin.pages.upwork-payments', compact('orders'));
    }
}
