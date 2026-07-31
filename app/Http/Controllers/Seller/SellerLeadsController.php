<?php

namespace App\Http\Controllers\Seller;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellerLeadsController extends Controller
{
    /**
     * Show all leads for seller (filtered by role & brand)
     */
    public function sellerLeads(Request $request)
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (!$seller && !$isAdmin) {
            return redirect()->route('seller.login.get')
                ->with('error', 'You must be logged in.');
        }

        $query = Lead::query()
            ->with([
                'brand:id,brand_name',
                'seller:id,name,email,brand_id,is_seller',
                'client:id,name,email',
                'assignments:id,lead_id,status,assigned_to,assigned_role,assigned_by'
            ])
            ->withCount([
                'paymentLinks as paid_links_count' => fn($q) => $q->where('status', 'paid'),
            ])
            ->addSelect([
                'latest_order_id' => Order::select('id')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
                'latest_order_balance_due' => Order::select('balance_due')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
                'latest_order_currency' => Order::select('currency')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
            ]);

        // Role-based filtering
        if ($seller) {
            $role = $seller->role ?? $seller->is_seller; // 'front_seller' | 'project_manager'
            if ($role === 'front_seller') {
                $query->where('brand_id', $seller->brand_id);
            } else {
                $query->where('brand_id', $seller->brand_id)
                    ->where(function ($w) use ($seller) {
                        $w->where('seller_id', $seller->id)
                            ->orWhereHas('assignments', fn ($a) => $a->where('assigned_to', $seller->id));
                    });
            }
        }

        // Filters
        if ($request->filled('status'))   $query->where('status', $request->string('status'));
        if ($request->filled('brand_id')) $query->where('brand_id', (int)$request->brand_id);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        $leads = $query->paginate(20)->withQueryString();

        // Project Managers in same brand (seller context only)
        $pmSellers = $seller
            ? Seller::where('is_seller', 'project_manager')
                ->where('brand_id', $seller->brand_id)
                ->get(['id', 'name', 'email', 'brand_id'])
            : collect();

        return view('sellers.pages.leads', compact('leads', 'pmSellers'));
    }

    /**
     * Lead Details View + Logging
     */
    public function sellerLeadDetails($id)
    {
        $lead = Lead::withoutGlobalScopes()->with([
            'brand:id,brand_name,brand_url',
            'seller:id,name,email,brand_id,is_seller',
            'client:id,name,email,phone'
        ])->findOrFail($id);

        $user = auth('seller')->user();
        $admin = auth('admin')->user();
        $actor = $admin ?: $user;

        abort_unless($actor, 403, 'Unauthorized.');
        Gate::forUser($actor)->authorize('view', $lead);

        $leadId = $lead->id;

        if ($user) {
            $brandSlug = Str::slug($lead->brand->brand_name ?? 'unknown-brand', '_');

            $sessionKey = "viewed_lead_{$leadId}";
            if (!session()->has($sessionKey)) {
                session()->put($sessionKey, now()->toDateTimeString());

                // Ensure folder exists
                $logDir = storage_path("logs/brands/{$brandSlug}");
                if (!File::exists($logDir)) {
                    File::makeDirectory($logDir, 0755, true);
                }

                // Write log file
                Log::build([
                    'driver' => 'single',
                    'path'   => "{$logDir}/lead-views.log",
                ])->info('Lead viewed', [
                    'seller_id'   => $user->id,
                    'seller_name' => $user->name,
                    'lead_id'     => $lead->id,
                    'lead_name'   => $lead->name,
                    'brand_name'  => $lead->brand->brand_name ?? 'N/A',
                    'viewed_at'   => now()->toDateTimeString(),
                    'ip'          => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                ]);
            }
        }

        return view('sellers.pages.lead-details', compact('lead'));
    }

    /**
     * Assigned Leads (Project Manager)
     */
    public function sellerAssignedLeads(Request $request)
    {
        $seller  = auth('seller')->user();
        $isAdmin = auth('admin')->check();

        if (!$seller && !$isAdmin) {
            return redirect()->route('seller.login.get')->with('error', 'You must be logged in.');
        }

        $query = Lead::query()
            ->with([
                'brand:id,brand_name',
                'seller:id,name,email,brand_id,is_seller',
                'client:id,name,email',
                'assignments:id,lead_id,status,assigned_to,assigned_role,assigned_by'
            ])
            ->withCount([
                'paymentLinks as paid_links_count' => fn($q) => $q->where('status', 'paid'),
            ])
            ->addSelect([
                'latest_order_id' => Order::select('id')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
                'latest_order_balance_due' => Order::select('balance_due')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
                'latest_order_currency' => Order::select('currency')
                    ->whereColumn('orders.lead_id', 'leads.id')
                    ->orderByDesc('orders.id')
                    ->limit(1),
            ]);

        if ($seller) {
            $query->whereHas('assignments', fn($q) => $q->where('assigned_to', $seller->id));
        }

        // Filters
        if ($request->filled('status'))   $query->where('status', $request->string('status'));
        if ($request->filled('brand_id')) $query->where('brand_id', (int)$request->brand_id);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        $leads = $query->paginate(20)->withQueryString();

        return view('sellers.pages.assigned-leads', compact('leads'));
    }
}
