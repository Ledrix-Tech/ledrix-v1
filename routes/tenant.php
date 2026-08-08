<?php

use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CrmAccessController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\BankTransferBillingController;
use App\Http\Controllers\Tenant\JazzCashBillingController;
use App\Http\Controllers\Tenant\OrganizationPlanController;
use App\Http\Controllers\Tenant\OrganizationSettingsController;
use App\Http\Controllers\Tenant\OrganizationDomainController;
use App\Http\Controllers\Tenant\OrganizationAuditLogController;
use App\Http\Controllers\Tenant\PayFastBillingController;
use App\Http\Controllers\Tenant\PlatformSupportController;
use App\Http\Controllers\Tenant\RegistrationController;
use App\Http\Controllers\Tenant\StripeBillingController;
use App\Http\Controllers\Tenant\TenantReferralController;
use Illuminate\Support\Facades\Route;

// ── Registration (public) ──────────────────────────────────
// Static paths MUST be registered before /register/{slug} or "success" is treated as a plan slug → 404.
Route::get('/register/success', [RegistrationController::class, 'success'])
    ->name('tenant.register.success');

Route::post('/register', [RegistrationController::class, 'store'])
    ->name('tenant.register.store');

Route::get('/register/{slug}', [RegistrationController::class, 'showForm'])
    ->where('slug', '[a-z0-9-]+')
    ->name('tenant.register.form');

Route::get('/verify-email/{token}', [RegistrationController::class, 'verify'])
    ->name('tenant.verify-email');

Route::post('/verify-email/resend', [RegistrationController::class, 'resend'])
    ->name('tenant.verify-email.resend');

// ── Tenant auth ────────────────────────────────────────────
Route::get('/sign-in', [AuthController::class, 'showLogin'])
    ->name('tenant.login');

Route::post('/sign-in', [AuthController::class, 'login'])
    ->name('tenant.login.post');

Route::post('/sign-out', [AuthController::class, 'logout'])
    ->name('tenant.logout');

// ── Tenant dashboard (authenticated) ───────────────────────
Route::middleware('tenant')->group(function () {
    Route::get('/tenant-profile', [DashboardController::class, 'index'])
        ->name('tenant.dashboard');

    Route::post('/tenant-profile/announcements/{id}/dismiss', [AnnouncementController::class, 'dismiss'])
        ->name('tenant.announcements.dismiss')
        ->whereNumber('id');

    Route::get('/tenant-profile/support', [PlatformSupportController::class, 'index'])
        ->name('tenant.support.index');
    Route::get('/tenant-profile/support/new', [PlatformSupportController::class, 'create'])
        ->name('tenant.support.create');
    Route::post('/tenant-profile/support', [PlatformSupportController::class, 'store'])
        ->name('tenant.support.store');
    Route::get('/tenant-profile/support/{id}', [PlatformSupportController::class, 'show'])
        ->name('tenant.support.show')
        ->whereNumber('id');
    Route::post('/tenant-profile/support/{id}/reply', [PlatformSupportController::class, 'reply'])
        ->name('tenant.support.reply')
        ->whereNumber('id');

    Route::get('/tenant-profile/plan', [OrganizationPlanController::class, 'index'])
        ->name('tenant.plan');

    Route::get('/tenant-profile/settings', [OrganizationSettingsController::class, 'edit'])
        ->name('tenant.settings');
    Route::put('/tenant-profile/settings', [OrganizationSettingsController::class, 'update'])
        ->name('tenant.settings.update');

    Route::get('/tenant-profile/audit-logs', [OrganizationAuditLogController::class, 'index'])
        ->name('tenant.audit-logs');

    Route::middleware('tenant.feature:custom_domain|white_label')->group(function () {
        Route::get('/tenant-profile/domain', [OrganizationDomainController::class, 'edit'])
            ->name('tenant.domain');
        Route::put('/tenant-profile/domain', [OrganizationDomainController::class, 'updateDomain'])
            ->name('tenant.domain.update');
        Route::post('/tenant-profile/domain/verify', [OrganizationDomainController::class, 'verifyDomain'])
            ->name('tenant.domain.verify');
        Route::post('/tenant-profile/domain/branding', [OrganizationDomainController::class, 'updateBranding'])
            ->name('tenant.domain.branding');
    });

    Route::get('/tenant-profile/billing', [BillingController::class, 'index'])
        ->name('tenant.billing');

    Route::get('/tenant-profile/billing/invoices/{invoice}', [\App\Http\Controllers\Tenant\TenantInvoiceController::class, 'show'])
        ->name('tenant.billing.invoice.show')
        ->whereNumber('invoice');

    Route::post('/tenant-profile/billing/currency', [BillingController::class, 'updateBillingCurrency'])
        ->name('tenant.billing.currency');

    Route::post('/tenant-profile/billing/auto-renew', [BillingController::class, 'updateAutoRenew'])
        ->name('tenant.billing.auto-renew');

    Route::post('/tenant-profile/billing/cancel', [BillingController::class, 'cancelAtPeriodEnd'])
        ->name('tenant.billing.cancel');

    Route::get('/tenant-profile/referrals', [TenantReferralController::class, 'index'])
        ->name('tenant.referrals');
    Route::post('/tenant-profile/referrals', [TenantReferralController::class, 'issue'])
        ->name('tenant.referrals.issue');

    Route::post('/tenant-profile/billing/stripe/checkout', [StripeBillingController::class, 'checkout'])
        ->name('tenant.billing.stripe.checkout');

    Route::post('/tenant-profile/billing/payfast/checkout', [PayFastBillingController::class, 'checkout'])
        ->name('tenant.billing.payfast.checkout');

    Route::post('/tenant-profile/billing/jazzcash/checkout', [JazzCashBillingController::class, 'checkout'])
        ->name('tenant.billing.jazzcash.checkout');

    Route::post('/tenant-profile/billing/bank-transfer/checkout', [BankTransferBillingController::class, 'checkout'])
        ->name('tenant.billing.bank-transfer.checkout');

    Route::get('/tenant-profile/billing/bank-transfer/{payment}', [BankTransferBillingController::class, 'show'])
        ->name('tenant.billing.bank-transfer.show');

    Route::post('/tenant-profile/billing/bank-transfer/{payment}/report', [BankTransferBillingController::class, 'report'])
        ->name('tenant.billing.bank-transfer.report');

    Route::get('/tenant-profile/go-crm', [CrmAccessController::class, 'enter'])
        ->name('tenant.goto-crm');
});

// JazzCash return (public — CSRF excluded)
Route::match(['get', 'post'], '/billing/jazzcash/return', [JazzCashBillingController::class, 'returnCallback'])
    ->name('tenant.billing.jazzcash.return');

Route::match(['get', 'post'], '/billing/stripe/success', [StripeBillingController::class, 'success'])
    ->name('tenant.billing.stripe.success');

Route::match(['get', 'post'], '/billing/payfast/success', [PayFastBillingController::class, 'success'])
    ->name('tenant.billing.payfast.success');
