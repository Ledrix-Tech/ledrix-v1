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
use App\Http\Controllers\Admin\AdminTwoFactorController;
use App\Http\Controllers\Admin\UpworkController;
use App\Http\Controllers\API\Client\BrandConfigController;
use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\BankTransferBillingController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\JazzCashBillingController;
use App\Http\Controllers\Tenant\OrganizationApiTokenController;
use App\Http\Controllers\Tenant\OrganizationAuditLogController;
use App\Http\Controllers\Tenant\OrganizationDomainController;
use App\Http\Controllers\Tenant\OrganizationOverviewController;
use App\Http\Controllers\Tenant\OrganizationPlanController;
use App\Http\Controllers\Tenant\OrganizationSettingsController;
use App\Http\Controllers\Tenant\OrganizationTeamController;
use App\Http\Controllers\Tenant\PayFastBillingController;
use App\Http\Controllers\Tenant\PlatformSupportController;
use App\Http\Controllers\Tenant\StripeBillingController;
use App\Http\Controllers\Tenant\TenantInvoiceController;
use App\Http\Controllers\Tenant\TenantReferralController;

Route::group(['prefix' => 'admin'], function () {
    Route::get('/login', [AdminAuthController::class, 'adminLoginPage'])->name('admin.login.get');
    Route::post('/login', [AdminAuthController::class, 'adminLoginPost'])->name('admin.login.post');
    Route::get('/forgot-password', [AdminAuthController::class, 'adminForgotPage'])->name('admin.forgot.get');
    Route::get('/reset/{token?}/password', [AdminAuthController::class, 'adminResetPage'])->name('admin.reset.get');
    Route::post('/forgot-password', [AdminAuthController::class, 'adminForgotPost'])->name('admin.forgot.post');
    Route::post('/reset-password', [AdminAuthController::class, 'adminResetPost'])->name('admin.reset.post');
    Route::post('/logout', [AdminAuthController::class, 'adminlogout'])->name('admin.logout');

    Route::get('/2fa/challenge', [AdminTwoFactorController::class, 'challenge'])->name('admin.2fa.challenge');
    Route::post('/2fa/challenge', [AdminTwoFactorController::class, 'verifyChallenge'])->name('admin.2fa.challenge.post');

    Route::group(['middleware' => ['admin', 'finance.restrict']], function () {
        Route::get('/2fa', [AdminTwoFactorController::class, 'showSetup'])->name('admin.2fa.setup');
        Route::post('/2fa/enable', [AdminTwoFactorController::class, 'enable'])->name('admin.2fa.enable');
        Route::post('/2fa/disable', [AdminTwoFactorController::class, 'disable'])->name('admin.2fa.disable');
    });

    Route::group(['middleware' => ['admin', 'finance.restrict', 'crm.workspace', 'tenant.feature:ppc_module']], function () {
        Route::get('/dashboard', [ViewsController::class, 'adminDashboard'])->name('admin.index.get');

        // Finance home — not gated by stripe|paypal so finance login never 403s.
        Route::get('/brand-payments', [AdminBrandsController::class, 'adminBrandPayments'])->name('admin.brand-payments.get');
        Route::get('/brand-payouts', [AdminBrandsController::class, 'adminBrandPayouts'])->name('admin.brand-payouts.get');

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

    // Organization portal — must NOT sit under tenant.feature:ppc_module.
    // Expired admins renew here; plan feature flags must not 403 billing.
    Route::group(['middleware' => ['admin', 'finance.restrict', 'crm.workspace', 'admin.only']], function () {
        Route::prefix('organization')->name('admin.org.')->group(function () {
            Route::get('/', [OrganizationOverviewController::class, 'index'])->name('overview');

            Route::get('/plan', [OrganizationPlanController::class, 'index'])->name('plan');

            Route::get('/settings', [OrganizationSettingsController::class, 'edit'])->name('settings');
            Route::put('/settings', [OrganizationSettingsController::class, 'update'])->name('settings.update');

            Route::get('/audit-logs', [OrganizationAuditLogController::class, 'index'])->name('audit-logs');

            Route::middleware('tenant.feature:custom_domain|white_label')->group(function () {
                Route::get('/domain', [OrganizationDomainController::class, 'edit'])->name('domain');
                Route::put('/domain', [OrganizationDomainController::class, 'updateDomain'])->name('domain.update');
                Route::post('/domain/verify', [OrganizationDomainController::class, 'verifyDomain'])->name('domain.verify');
                Route::post('/domain/branding', [OrganizationDomainController::class, 'updateBranding'])->name('domain.branding');
            });

            Route::get('/team', [OrganizationTeamController::class, 'index'])->name('team');
            Route::post('/team', [OrganizationTeamController::class, 'store'])->name('team.store');
            Route::delete('/team/{id}', [OrganizationTeamController::class, 'destroy'])->name('team.destroy')->whereNumber('id');

            Route::middleware('tenant.feature:api_access')->group(function () {
                Route::get('/api-tokens', [OrganizationApiTokenController::class, 'index'])->name('api-tokens');
                Route::post('/api-tokens', [OrganizationApiTokenController::class, 'store'])->name('api-tokens.store');
                Route::post('/api-tokens/{id}/revoke', [OrganizationApiTokenController::class, 'revoke'])
                    ->name('api-tokens.revoke')
                    ->whereNumber('id');
            });

            Route::get('/billing', [BillingController::class, 'index'])->name('billing');
            Route::get('/billing/invoices/{invoice}', [TenantInvoiceController::class, 'show'])->name('billing.invoice.show')->whereNumber('invoice');
            Route::post('/billing/currency', [BillingController::class, 'updateBillingCurrency'])->name('billing.currency');
            Route::post('/billing/auto-renew', [BillingController::class, 'updateAutoRenew'])->name('billing.auto-renew');
            Route::post('/billing/cancel', [BillingController::class, 'cancelAtPeriodEnd'])->name('billing.cancel');
            Route::post('/billing/stripe/checkout', [StripeBillingController::class, 'checkout'])->name('billing.stripe.checkout');
            Route::post('/billing/payfast/checkout', [PayFastBillingController::class, 'checkout'])->name('billing.payfast.checkout');
            Route::post('/billing/jazzcash/checkout', [JazzCashBillingController::class, 'checkout'])->name('billing.jazzcash.checkout');
            Route::post('/billing/bank-transfer/checkout', [BankTransferBillingController::class, 'checkout'])->name('billing.bank-transfer.checkout');
            Route::get('/billing/bank-transfer/{payment}', [BankTransferBillingController::class, 'show'])->name('billing.bank-transfer.show');
            Route::post('/billing/bank-transfer/{payment}/report', [BankTransferBillingController::class, 'report'])->name('billing.bank-transfer.report');

            Route::get('/support', [PlatformSupportController::class, 'index'])->name('support.index');
            Route::get('/support/new', [PlatformSupportController::class, 'create'])->name('support.create');
            Route::post('/support', [PlatformSupportController::class, 'store'])->name('support.store');
            Route::get('/support/{id}', [PlatformSupportController::class, 'show'])->name('support.show')->whereNumber('id');
            Route::post('/support/{id}/reply', [PlatformSupportController::class, 'reply'])->name('support.reply')->whereNumber('id');

            Route::get('/referrals', [TenantReferralController::class, 'index'])->name('referrals');
            Route::post('/referrals', [TenantReferralController::class, 'issue'])->name('referrals.issue');

            Route::post('/announcements/{id}/dismiss', [AnnouncementController::class, 'dismiss'])
                ->name('announcements.dismiss')
                ->whereNumber('id');
        });
    });
});
