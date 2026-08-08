<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Seller\WebhookController;
use App\Http\Controllers\Seller\PayPalPaymentController;

Route::middleware('portal.auth')->group(function () {
    Route::get('/user-profile', [ProfileController::class, 'authProfile'])->name('auth.profile.get');
    Route::post('/user-profile-update', [ProfileController::class, 'updateProfile'])->name('auth.profile.update');
});

Route::middleware(['admin_or_seller', 'crm.workspace', 'portal.tenant.feature:ppc_module'])->group(function () {
    Route::post('/lead-update-status', [ManagementController::class, 'updateLeadStatus'])->name('lead.update-status');
    Route::post('/lead-assign', [AdminSellerController::class, 'assignLeadSeller'])->name('lead-assign.post');
    Route::post('/paylink-status', [ManagementController::class, 'changePaylinkStatus'])
        ->name('change.paylink-status');

    Route::middleware('portal.tenant.feature:client_portal')->group(function () {
        Route::post('/client-account-access', [ManagementController::class, 'clientAccountAccess'])->name('client.account-access');
    });

    Route::middleware('portal.tenant.feature:support_tickets')->group(function () {
        Route::post('/ticket-update-status', [ExportController::class, 'updateTicketStatus'])->name('ticket.update-status');
    });

    Route::middleware('portal.tenant.feature:dual_invoicing')->group(function () {
        Route::get('/generate/{order?}/invoice', [ManagementController::class, 'generateInvoice'])
            ->whereNumber('order')
            ->name('order.generate-invoice');
    });

    Route::middleware('portal.tenant.feature:stripe|paypal')->group(function () {
        Route::prefix('brand')->group(function () {
            Route::get('/{brand?}/lead/{lead?}/generate-link/{order?}', [CheckoutController::class, 'generateLinkForm'])
                ->whereNumber(['brand', 'lead', 'order'])
                ->name('generate-link-form');

            Route::post('/{brand?}/lead/{lead?}/generate-link', [CheckoutController::class, 'generatePayLink'])
                ->name('generate-payment-link');

            Route::get('/{brand?}/lead/{lead?}/renew-order/{order?}/', [CheckoutController::class, 'renewOrderLink'])
                ->whereNumber(['brand', 'lead', 'order'])
                ->name('renew-order-link');
        });
    });
});

Route::prefix('pay')->name('paylinks.')->group(function () {
    Route::get('/now/{token?}', [WebhookController::class, 'showPaymentPage'])->name('show');
    Route::post('/now/{token?}/checkout', [WebhookController::class, 'createCheckout'])->name('checkout');
    Route::get('/now/{token?}/success', [WebhookController::class, 'checkoutSuccess'])->name('success');
    Route::get('/now/{token?}/cancel', [WebhookController::class, 'checkoutCancel'])->name('cancel');
    Route::get('/now/{token?}/error', [WebhookController::class, 'checkoutError'])->name('error');
});

Route::get('/pay/paypal/{token?}/return', [PayPalPaymentController::class, 'paypalReturn'])->name('paypal.return');
Route::get('/payments/{token}/success', [PayPalPaymentController::class, 'successPaid'])->name('payments.thanks');
