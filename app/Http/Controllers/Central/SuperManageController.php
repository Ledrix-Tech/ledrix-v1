<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\Central\Tenant;
use App\Models\Central\TenantLimit;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantRenewalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class SuperManageController extends Controller
{
    public function superCompanyAnalytics($id)
    {
        $company = Tenant::with(['plan'])->findOrFail($id);
        $subscription = TenantMembership::where('tenant_id', $id)->latest()->first();
        $limits = TenantLimit::where('tenant_id', $id)->first();

        $totalAdmins   = Admin::where('tenant_id', $id)->count();
        $totalUsers    = Seller::where('tenant_id', $id)->count();
        $totalClients  = Client::where('tenant_id', $id)->count();
        $totalLeads    = Lead::where('tenant_id', $id)->count();
        $totalDeals    = Order::where('tenant_id', $id)->count();
        $totalPayments = Payment::where('tenant_id', $id)->sum('amount');

        $recentLeads = Lead::where('tenant_id', $id)
            ->latest()
            ->take(5)
            ->get(['name', 'status', 'created_at']);

        $usage = [
            'admins'  => $limits ? $this->usagePercent($totalAdmins, $limits->max_admins) : 0,
            'users'   => $limits ? $this->usagePercent($totalUsers, $limits->max_users) : 0,
            'clients' => $limits ? $this->usagePercent($totalClients, $limits->max_clients) : 0,
            'leads'   => $limits ? $this->usagePercent($totalLeads, $limits->max_leads) : 0,
        ];

        return view('central.pages.company-analytics', compact(
            'company',
            'subscription',
            'limits',
            'totalAdmins',
            'totalUsers',
            'totalClients',
            'totalLeads',
            'totalDeals',
            'totalPayments',
            'recentLeads',
            'usage'
        ));
    }

    public function exportLeadsCsv(Request $request, string $table, int $companyId): StreamedResponse
    {
        if (! Schema::hasTable($table)) {
            abort(404, 'Table not found.');
        }

        $company = Tenant::find($companyId);
        if (! $company) {
            abort(404, 'Tenant not found.');
        }

        if (! Schema::hasColumn($table, 'tenant_id')) {
            abort(400, "The table '$table' does not have a 'tenant_id' column.");
        }

        $rowCount = DB::table($table)->where('tenant_id', $companyId)->count();
        if ($rowCount < 1) {
            abort(403, "CSV export allowed only for 100+ rows. Found: $rowCount");
        }

        $columns = $request->query('columns');
        if ($columns) {
            $columns = array_filter(explode(',', $columns), fn ($col) => Schema::hasColumn($table, trim($col)));
        } else {
            $columns = Schema::getColumnListing($table);
        }

        array_unshift($columns, 'tenant_name');

        $filename = Str::slug($company->name, '_') . "_{$table}_" . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($table, $columns, $company, $companyId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            DB::table($table)
                ->where('tenant_id', $companyId)
                ->orderBy('id')
                ->chunk(1000, function ($rows) use ($handle, $columns, $company) {
                    foreach ($rows as $row) {
                        $rowData = array_merge(['tenant_name' => $company->name], (array) $row);
                        fputcsv($handle, $rowData);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    public function superCompanyStatus(Request $request)
    {
        $tenant = Tenant::find($request->user_id);

        if (! $tenant) {
            return response()->json(['success' => false]);
        }

        $tenant->status = $request->status;
        $tenant->save();

        return response()->json(['success' => true]);
    }

    public function superAllRenewals()
    {
        $renewals = TenantRenewalRequest::with(['tenant', 'plan'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('central.pages.renewal-requests', compact('renewals'));
    }

    private function usagePercent(int $used, int $max): float
    {
        if ($max <= 0 || $max === -1) {
            return 0;
        }

        return round(($used / $max) * 100, 1);
    }
}
