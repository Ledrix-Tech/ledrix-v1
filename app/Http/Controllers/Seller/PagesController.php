<?php

namespace App\Http\Controllers\Seller;

use App\Models\Lead;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Client;
use App\Models\Seller;
use App\Models\Payment;
use App\Models\RiskyClient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LeadScriptService;
use App\Http\Controllers\Controller;
use App\Models\Questionnair;
use App\Support\PortalAuthorization;
use Illuminate\Support\Facades\File;

class PagesController extends Controller
{
    /**
     * Seller Domain Scripts
     */
    public function sellerDomainScripts(LeadScriptService $leadScripts)
    {
        $seller = auth('seller')->user();
        abort_unless($seller, 403, 'Unauthorized access.');

        $brand = Brand::query()->find($seller->brand_id);
        abort_unless($brand, 404, 'Brand not found.');

        return view('sellers.pages.domain-script', [
            'brand'         => $brand,
            'scriptService' => $leadScripts,
        ]);
    }

    /**
     * Seller Dashboard (auth:seller only)
     */
    public function sellerDashboard()
    {
        $seller = Seller::with('brand:id,brand_name,brand_url')
            ->find(auth('seller')->id());

        abort_unless($seller, 403, 'Unauthorized access.');

        if ($seller->is_seller === 'finance') {
            return redirect()
                ->route('admin.brand-payments.get')
                ->with('info', 'Finance accounts cannot access dashboard.');
        }

        $brandId = $seller->brand_id;
        $year = now()->year;

        // --- KPI counts (single round-trip) ---
        $stats = DB::selectOne('
            SELECT
                (SELECT COUNT(*) FROM leads WHERE brand_id = ? AND deleted_at IS NULL) AS leads,
                (SELECT COUNT(*) FROM brands WHERE id = ?) AS brands,
                (SELECT COUNT(*) FROM orders WHERE brand_id = ? AND deleted_at IS NULL) AS orders,
                (SELECT COUNT(*)
                    FROM payments p
                    INNER JOIN orders o ON o.id = p.order_id
                    WHERE o.brand_id = ? AND p.deleted_at IS NULL AND o.deleted_at IS NULL) AS payments
        ', [$brandId, $brandId, $brandId, $brandId]);

        // --- Active users query removed (unused in dashboard view) ---

        // --- Monthly revenue + payment summary (parallel aggregates) ---
        $revenueData = DB::table('orders')
            ->selectRaw('MONTH(created_at) as month, SUM(unit_amount)/100 as total_income')
            ->where('status', 'paid')
            ->where('brand_id', $brandId)
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = $revenueData->map(fn ($r) => date('F', mktime(0, 0, 0, (int) $r->month, 1)))->all();
        $totals = $revenueData->pluck('total_income')->all();

        $paymentSummary = DB::table('payment_links')
            ->selectRaw("
                SUM(CASE WHEN status = 'paid' THEN unit_amount ELSE 0 END) as paid,
                SUM(order_total_snapshot) as snapshot
            ")
            ->where('brand_id', $brandId)
            ->first();

        $paymentPaid  = (int) ($paymentSummary->paid ?? 0);
        $paymentTotal = (int) ($paymentSummary->snapshot ?? 0);
        $paymentDue   = $paymentTotal - $paymentPaid;
        $revenue      = $paymentPaid;

        // --- Lead view logs (per brand) ---
        $brand = $seller->brand;
        $brandSlug = Str::slug($brand?->brand_name ?? 'unknown-brand', '_');
        $logPath = storage_path("logs/brands/{$brandSlug}/lead-views.log");

        $logs = [];

        if (File::exists($logPath)) {
            try {
                $logs = self::tailLogLines($logPath, 20);
            } catch (\Throwable $e) {
                $logs = ['Unable to read lead view logs.'];
            }
        } else {
            $logs = ['No lead view logs found for ' . ($brand?->brand_name ?? 'your brand') . '.'];
        }


        return view('sellers.pages.index', [
            'leads'         => (int) ($stats->leads ?? 0),
            'brands'        => (int) ($stats->brands ?? 0),
            'orders'        => (int) ($stats->orders ?? 0),
            'payments'      => (int) ($stats->payments ?? 0),
            'seller'        => $seller,
            'months'        => $months,
            'totals'        => $totals,
            'revenue'       => $revenue,
            'paymentPaid'   => $paymentPaid,
            'paymentDue'    => $paymentDue,
            'logs'          => $logs,
        ]);
    }

    /**
     * Seller Clients
     */
    public function sellerClients(Request $request)
    {
        $seller = auth('seller')->user();
        abort_unless($seller, 403, 'Unauthorized access.');

        $role = $seller->role ?? $seller->is_seller;

        $clientsQuery = Client::query();

        if ($role === 'front_seller') {
            $clientsQuery->where(function ($q) use ($seller) {
                $q->whereHas('orders', fn ($o) => $o->where('brand_id', $seller->brand_id))
                    ->orWhereHas('leads', fn ($l) => $l->where('brand_id', $seller->brand_id));
            });
        } else {
            $clientsQuery->where(function ($q) use ($seller) {
                $q->whereHas('orders', fn ($o) => $o->where(function ($w) use ($seller) {
                    $w->where('seller_id', $seller->id)->orWhere('owner_seller_id', $seller->id);
                }))
                    ->orWhereHas('leads', fn ($l) => $l->where('seller_id', $seller->id));
            });
        }

        $search = trim((string) ($request->input('q') ?: $request->input('search') ?: ''));
        if ($search !== '') {
            $clientsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $clientsQuery->paginate(20)->withQueryString();

        $riskyClients = RiskyClient::with([
            'client:id,name,email',
            'client.orders.payments:id,order_id,amount,status,created_at',
        ])
            ->whereHas('client', function ($q) use ($seller, $role) {
                if ($role === 'front_seller') {
                    $q->whereHas('orders', fn ($o) => $o->where('brand_id', $seller->brand_id))
                        ->orWhereHas('leads', fn ($l) => $l->where('brand_id', $seller->brand_id));
                } else {
                    $q->whereHas('orders', fn ($o) => $o->where('seller_id', $seller->id))
                        ->orWhereHas('leads', fn ($l) => $l->where('seller_id', $seller->id));
                }
            })
            ->orderByDesc('risk_score')
            ->limit(20)
            ->get();

        return view('sellers.pages.clients', compact('clients', 'riskyClients'));
    }

    /** Read the last N non-empty lines without loading the whole file into memory. */
    private static function tailLogLines(string $path, int $lines = 20): array
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = (int) $file->key();
        $start = max(0, $lastLine - max($lines * 3, $lines));

        $buffer = [];
        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $line = trim((string) $file->current());
            if ($line !== '') {
                $buffer[] = $line;
            }
        }

        return array_slice(array_reverse($buffer), 0, $lines);
    }
}
