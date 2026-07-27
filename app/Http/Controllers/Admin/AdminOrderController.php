<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminOrdersService;
use App\Services\Admin\AdminPaymentsService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function adminOrders(Request $request, AdminOrdersService $orders)
    {
        if (! auth('admin')->check()) {
            return redirect()->route('admin.login.get')->with('error', 'You must be logged in.');
        }

        $orders = $orders->paginatedOriginalOrders($request);

        return view('admin.pages.orders', compact('orders'));
    }

    public function adminOrderRenewals(Request $request, int $order, AdminOrdersService $ordersService)
    {
        if (! auth('seller')->check() && ! auth('admin')->check()) {
            return redirect()->route('admin.login.get')
                ->with('error', 'You must be logged in.');
        }

        try {
            $payload = $ordersService->renewalsPayload($order);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        return view('admin.pages.renewed-orders', $payload);
    }

    public function adminPMOrders(Request $request, AdminOrdersService $orders)
    {
        if (! auth('seller')->user() && ! auth('admin')->check()) {
            return redirect()->route('admin.login.get')->with('error', 'You must be logged in.');
        }

        $orders = $orders->paginatedPmOrders($request);

        return view('admin.pages.pm-orders', compact('orders'));
    }

    public function adminPayments(Request $request, AdminPaymentsService $payments)
    {
        if (! auth('admin')->check()) {
            return redirect()->route('admin.login.get')->with('error', 'You must be logged in.');
        }

        $payments = $payments->paginatedList($request);

        return view('admin.pages.payment-data', compact('payments'));
    }
}
