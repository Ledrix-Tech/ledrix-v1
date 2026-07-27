<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminClientsService;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminAccountKeysService;

class ViewsController extends Controller
{
    public function adminDashboard(AdminDashboardService $dashboard)
    {
        $admin = auth('admin')->user();

        if ($admin && $admin->role === 'finance') {
            return redirect()
                ->route('admin.brand-payments.get')
                ->with('info', 'Finance accounts cannot access dashboard.');
        }

        $brandId = request()->filled('id') ? (int) request('id') : null;

        return view('admin.pages.index', $dashboard->build($brandId));
    }

    public function adminClients(AdminClientsService $clients)
    {
        $search = request()->filled('search') ? (string) request('search') : null;

        return view('admin.pages.clients', [
            'clients' => $clients->paginatedList($search),
            'search'  => $search ?? '',
        ]);
    }

    public function adminAccountKeys(AdminAccountKeysService $accountKeys)
    {
        return view('admin.pages.account-keys', $accountKeys->pageData());
    }

    public function accountKeyStore(Request $request, AdminAccountKeysService $accountKeys)
    {
        $accountKeys->store($request);

        return back()->with('success', 'Payment keys saved successfully.');
    }

    public function accountKeysUpdate(Request $request, $id, AdminAccountKeysService $accountKeys)
    {
        $accountKeys->update($request, (int) $id);

        return back()->with('success', 'Keys updated successfully.');
    }
}
