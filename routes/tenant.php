<?php

use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CrmAccessController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\BankTransferBillingController;
use App\Http\Controllers\Tenant\JazzCashBillingController;
use App\Http\Controllers\Tenant\PayFastBillingController;
use App\Http\Controllers\Tenant\RegistrationController;
use App\Http\Controllers\Tenant\StripeBillingController;
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

    Route::get('/tenant-profile/billing', [BillingController::class, 'index'])
        ->name('tenant.billing');

    Route::post('/tenant-profile/billing/stripe/checkout', [StripeBillingController::class, 'checkout'])
        ->name('tenant.billing.stripe.checkout');

    Route::post('/tenant-profile/billing/payfast/checkout', [PayFastBillingController::class, 'checkout'])
        ->name('tenant.billing.payfast.checkout');

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
