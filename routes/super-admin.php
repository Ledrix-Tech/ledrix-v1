<?php

use App\Http\Controllers\Central\StripeController;
use App\Http\Controllers\Central\MembershipController;
use App\Http\Controllers\Central\SubscriptionPaymentController;
use App\Http\Controllers\Central\SuperDashboardController;
use App\Http\Controllers\Central\AuthorizeController;
use App\Http\Controllers\Central\TenantFeatureController;
use App\Http\Controllers\Tenant\ContactQueryController;
use App\Http\Controllers\Central\PricingController;
use Illuminate\Support\Facades\Route;

// super admin routes
Route::group(['prefix' => 'super-admin', 'namespace' => 'SuperAdmin'], function () {
    //admin auth
    Route::get('/login', [AuthorizeController::class, 'adminLoginPage'])->name('super-admin.login.get');
    Route::post('/login', [AuthorizeController::class, 'adminLoginPost'])->name('super-admin.login.post');
    Route::get('/forgot-password', [AuthorizeController::class, 'adminForgotPage'])->name('super-admin.forgot.get');
    Route::get('/reset/{token?}/password', [AuthorizeController::class, 'adminResetPage'])->name('super-admin.reset.get');
    Route::post('/forgot-password', [AuthorizeController::class, 'adminForgotPost'])->name('super-admin.forgot.post');
    Route::post('/reset-password', [AuthorizeController::class, 'adminResetPost'])->name('super-admin.reset.post');
    Route::get('/logout', [AuthorizeController::class, 'adminlogout'])->name('super-admin.logout');

    // admin views
    Route::group(['middleware' => 'super-admin'], function () {

        Route::get('/dashboard', [SuperDashboardController::class, 'superDashboard'])->name('super-admin.index.get');

        Route::get('/contact-queries', [ContactQueryController::class, 'superContactQuries'])->name('super-admin.contact-queries.get');
        Route::post('/contact-update-status', [ContactQueryController::class, 'updateContactStatus'])
            ->name('super-admin.contact-status.post');

        Route::get('/pricing-packages', [PricingController::class, 'superPackegsPricing'])->name('super-admin.pricing-packages.get');
        Route::post('/pricing-packages', [PricingController::class, 'pricingPackageStore'])->name('super-admin.pricing-packages.post');
        Route::put('/pricing-package/{id?}/update', [PricingController::class, 'pricingPackageUpdate'])->name('super-admin.pricing-packages.update');

        Route::get('/company-profile', [MembershipController::class, 'superCompanyProfiles'])->name('super-admin.company-profile.get');
        Route::get('/company/{id}', [MembershipController::class, 'superTenantShow'])->name('super-admin.tenant.show')->whereNumber('id');
        Route::post('/company-status', [MembershipController::class, 'superTenantStatus'])->name('super-company.company-status');

        Route::get('/company/{tenantId}/features', [TenantFeatureController::class, 'edit'])->name('super-admin.tenant.features.get')->whereNumber('tenantId');
        Route::put('/company/{tenantId}/features', [TenantFeatureController::class, 'update'])->name('super-admin.tenant.features.update')->whereNumber('tenantId');
        Route::delete('/company/{tenantId}/features', [TenantFeatureController::class, 'reset'])->name('super-admin.tenant.features.reset')->whereNumber('tenantId');

        Route::get('/subscription-payments', [SubscriptionPaymentController::class, 'pending'])->name('super-admin.subscription-payments.get');
        Route::post('/subscription-payments/{paymentId}/confirm', [SubscriptionPaymentController::class, 'confirm'])->name('super-admin.subscription-payments.confirm');

        Route::post('/company/{tenant}/send-renewal-approval', [StripeController::class, 'sendRenewalApproval'])
            ->name('super-renew.send')
            ->whereNumber('tenant');
    });
});
