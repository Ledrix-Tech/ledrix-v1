<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ManagementController;
use App\Http\Controllers\Admin\LeadsController;
use App\Http\Controllers\Admin\ViewsController;

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminBrandsController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Admin\UpworkController;
use App\Http\Controllers\API\Client\BrandConfigController;

Route::group(['prefix' => 'admin'], function () {
    Route::get('/login', [AdminAuthController::class, 'adminLoginPage'])->name('admin.login.get');
    Route::post('/login', [AdminAuthController::class, 'adminLoginPost'])->name('admin.login.post');
    Route::get('/forgot-password', [AdminAuthController::class, 'adminForgotPage'])->name('admin.forgot.get');
    Route::get('/reset/{token?}/password', [AdminAuthController::class, 'adminResetPage'])->name('admin.reset.get');
    Route::post('/forgot-password', [AdminAuthController::class, 'adminForgotPost'])->name('admin.forgot.post');
    Route::post('/reset-password', [AdminAuthController::class, 'adminResetPost'])->name('admin.reset.post');
    Route::post('/logout', [AdminAuthController::class, 'adminlogout'])->name('admin.logout');

    Route::group(['middleware' => ['admin', 'finance.restrict', 'crm.workspace', 'tenant.feature:ppc_module']], function () {
        Route::get('/dashboard', [ViewsController::class, 'adminDashboard'])->name('admin.index.get');

        // Finance + admin: brand payment reports
        Route::middleware('tenant.feature:stripe|paypal')->group(function () {
            Route::get('/brand-payments', [AdminBrandsController::class, 'adminBrandPayments'])->name('admin.brand-payments.get');
            Route::get('/brand-payouts', [AdminBrandsController::class, 'adminBrandPayouts'])->name('admin.brand-payouts.get');
        });

        // Administrators only — not finance, not sellers
        Route::middleware('admin.only')->group(function () {
            Route::middleware('tenant.feature:stripe|paypal')->group(function () {
                Route::get('/account-keys', [ViewsController::class, 'adminAccountKeys'])->name('admin.account-keys.get');
                Route::post('/account-keys', [ViewsController::class, 'accountKeyStore'])->name('admin.account-keys.post');
                Route::post('/account-keys/{id?}/update', [ViewsController::class, 'accountKeysUpdate'])->name('admin.account-keys-update');
            });

            Route::get('/brands', [AdminBrandsController::class, 'adminBrands'])->name('admin.brands.get');
            Route::post('/brand-post', [AdminBrandsController::class, 'adminBrandPost'])->name('admin.brand.post');

            Route::middleware('tenant.feature:api_access')->group(function () {
                Route::get('/domain-scripts', [BrandConfigController::class, 'adminDomainScripts'])->name('admin.domain-script.get');
                Route::post('/domain-scripts', [BrandConfigController::class, 'domainScriptStore'])->name('admin.domain-scripts.post');
                Route::put('/domain-scripts/{brand?}/update', [BrandConfigController::class, 'domainScriptUpdate'])->name('admin.domain-scripts-update');
                Route::post('/domain-scripts/{brand}/test-lead', [BrandConfigController::class, 'testLeadCapture'])->name('admin.domain-scripts.test-lead');
                Route::get('/domain-scripts/{brand}/script-status', [BrandConfigController::class, 'checkScriptStatus'])->name('admin.domain-scripts.script-status');
            });

            Route::get('/sellers', [AdminSellerController::class, 'adminSellers'])->name('admin.sellers.get');
            Route::post('/seller-post', [AdminSellerController::class, 'adminSellerPost'])->name('admin.seller.post');
            Route::get('/seller/{id?}/performance', [AdminSellerController::class, 'adminSellerPerformance'])->name('admin.seller-performance.get');
            Route::post('/seller-status', [AdminSellerController::class, 'sellerUpdateStatus'])->name('admin.seller.updateStatus');

            Route::post('/client-delete', [ManagementController::class, 'deleteClient'])->name('admin.client.delete');
            Route::post('/domain-delete', [ManagementController::class, 'deleteDomain'])->name('admin.domain.delete');
            Route::post('/domain-status', [ManagementController::class, 'updateDomainStatus'])->name('admin.domain.updateStatus');
            Route::post('/lead-delete/{id?}', [ManagementController::class, 'deleteLeads'])->name('admin.leads.delete');
            Route::post('/lead-views/clear', [LeadsController::class, 'clearLeadViewLogs'])->name('admin.lead.logs.clear');
            Route::get('/export/{table?}', [ExportController::class, 'exportLeadsCsv'])->name('export.csv')->whereIn('table', ['leads', 'clients', 'orders', 'payments', 'upwork_orders']);

            Route::middleware('tenant.feature:upwork_module')->group(function () {
                Route::get('/upwork-clients', [UpworkController::class, 'upworkClients'])->name('admin.upwork-clients.get');
                Route::get('/upwork-orders', [UpworkController::class, 'upworkOrders'])->name('admin.upwork-orders.get');
                Route::get('/upwork-payments', [UpworkController::class, 'upworkPayments'])->name('admin.upwork-payments.get');
            });

            Route::middleware('tenant.feature:support_tickets')->group(function () {
                Route::post('/ticket-delete/{id?}', [ExportController::class, 'deleteTickets'])->name('admin.tickets.delete');
            });
        });

        // Admin + sellers (finance blocked by finance.restrict)
        Route::get('/clients', [ViewsController::class, 'adminClients'])->name('admin.clients.get');
        Route::post('/client-status', [ManagementController::class, 'updateClientStatus'])->name('admin.client.updateStatus');

        Route::get('/leads', [LeadsController::class, 'adminLeads'])->name('admin.leads.get');
        Route::get('/lead/{id?}/details', [LeadsController::class, 'adminLeadDetails'])->name('admin.lead-details.get');
        Route::get('/assigned-leads', [LeadsController::class, 'sellerAssignedLeads'])->name('admin.assigned-leads.get');

        Route::get('/orders', [AdminOrderController::class, 'adminOrders'])->name('admin.orders.get');
        Route::get('/renewed/{order?}/orders', [AdminOrderController::class, 'adminOrderRenewals'])->name('admin.renewed-orders.get');
        Route::get('/assigned-leads-orders', [AdminOrderController::class, 'adminPMOrders'])->name('admin.assigned-leads-orders.get');

        Route::middleware('tenant.feature:stripe|paypal')->group(function () {
            Route::get('/payments', [AdminOrderController::class, 'adminPayments'])->name('admin.payments.get');
        });

        Route::middleware('tenant.feature:support_tickets')->group(function () {
            Route::get('/order/{order?}/tickets', [ExportController::class, 'adminOrderTickets'])->name('admin.order-tickets.get');
            Route::get('/order/ticket/{id?}/details', [ExportController::class, 'getTicketDetails'])->name('admin.tickets.details');
        });
    });
});
