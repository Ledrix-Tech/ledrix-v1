<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Contact;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;

class SuperDashboardController extends Controller
{
    public function superDashboard()
    {
        $totalTenants = Tenant::on('central')->count();

        $activeTenants = Tenant::on('central')
            ->where('status', 'active')
            ->count();

        $pendingEmailTenants = Tenant::on('central')
            ->whereNull('email_verified_at')
            ->count();

        $newTenantsThisMonth = Tenant::on('central')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $pendingContactQueries = Contact::on('central')
            ->where('status', 'new')
            ->count();

        $pendingSubscriptionPayments = TenantPayment::on('central')
            ->where('gateway', 'payoneer')
            ->where('status', 'pending')
            ->count();

        $recentTenants = Tenant::on('central')
            ->with(['plan:id,name,slug'])
            ->latest()
            ->take(8)
            ->get();

        $recentInvoices = TenantInvoice::on('central')
            ->with(['tenant:id,name,email', 'payment'])
            ->latest('issued_at')
            ->take(8)
            ->get();

        return view('central.pages.index', compact(
            'totalTenants',
            'activeTenants',
            'pendingEmailTenants',
            'newTenantsThisMonth',
            'pendingContactQueries',
            'pendingSubscriptionPayments',
            'recentTenants',
            'recentInvoices',
        ));
    }
}
