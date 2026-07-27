<?php

namespace App\Http\Controllers\Upwork;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Models\Upwork\UpworkOrder;
use Illuminate\Support\Facades\DB;
use App\Models\Upwork\UpworkClient;
use App\Http\Controllers\Controller;
use App\Models\Upwork\UpworkPayment;
use Illuminate\Support\Facades\File;

class ViewsController extends Controller
{
    public function upworkDashboard()
    {
        // Stats for Upwork orders and payments
        $brands = Brand::where('module', 'upwork')->count(); // Count of brands (since Upwork uses brands)
        $orders = UpworkOrder::count(); // Use UpworkOrder table
        $payments = UpworkPayment::count(); // Use UpworkPayment table to count actual payments
        // --- Revenue Calculation ---
        // Fetch Upwork orders and their total income (only for paid orders)
        $data = DB::table('upwork_orders') // Use the UpworkOrder table
            ->selectRaw('MONTH(created_at) as month, SUM(unit_amount) / 100 as total_income')
            ->where('status', 'paid') // Only paid orders
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $totals = [];
        foreach ($data as $row) {
            $months[] = date("F", mktime(0, 0, 0, $row->month, 1)); // Convert month number to name
            $totals[] = $row->total_income; // Store total income for each month
        }

        // --- Payments Calculation ---
        // Get payment links for Upwork orders
        $orderPayments = DB::table('upwork_payment_links') // Use UpworkPaymentLink table
            ->select('order_id')
            ->selectRaw('MAX(order_total_snapshot) as snapshot')
            ->selectRaw('SUM(CASE WHEN status = "paid" THEN unit_amount ELSE 0 END) as paid')
            ->groupBy('order_id')
            ->get();

        $paymentPaidAmount = $orderPayments->sum('paid');
        $totalOrderSnapshot = $orderPayments->sum('snapshot');
        $paymentDueAmount = $totalOrderSnapshot - $paymentPaidAmount;

        // Calculate revenue (from payments, not orders)
        $revenue = $paymentPaidAmount; // Paid amount from payments

        // --- Lead view logs (for all brands) ---
        $logs = [];
        $logsDir = storage_path("logs/brands");
        if (File::exists($logsDir)) {
            $brandLogs = File::allFiles($logsDir);
            $allLines = [];
            foreach ($brandLogs as $file) {
                try {
                    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        $allLines[] = '[' . basename($file->getPath()) . '] ' . $line;
                    }
                } catch (\Throwable $e) {
                    $allLines[] = "⚠️ Failed to read {$file->getFilename()}: " . $e->getMessage();
                }
            }
            $logs = collect($allLines)
                ->sortDesc() // Most recent logs first
                ->take(50)   // Limit for performance
                ->values()
                ->toArray();
        } else {
            $logs = ["No brand lead view logs found."];
        }

        return view('upwork.pages.index', [
            'orders' => $orders,
            'brands' => $brands,
            'payments' => $payments,
            'months' => $months,
            'totals' => $totals,
            'revenue' => $revenue,
            'paymentPaid' => $paymentPaidAmount,
            'paymentDue' => $paymentDueAmount,
            'logs' => $logs
        ]);
    }

    public function upworkClients()
    {
        $clients = UpworkClient::paginate(20);

        return view('upwork.pages.clients', [
            'clients' => $clients,
        ]);
    }

    public function generateInvoice(Request $request, ?UpworkOrder $order = null)
    {
        $order?->loadMissing('brand');
        $brand = $order?->brand;

        $module = 'upwork';

        $brandData = $brand
            ? Brand::where('id', $brand->id)
            ->where('module', $module)
            ->where('status', 'Active')
            ->first()
            : null;

        // dd($order, $brandData);

        return view('upwork.pages.invoice', compact('order', 'brand', 'brandData', 'module'));
    }
}
