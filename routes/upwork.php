<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Upwork\AuthController;
use App\Http\Controllers\upwork\CrudController;
use App\Http\Controllers\Upwork\ViewsController;
use App\Http\Controllers\Upwork\OrdersController;
use App\Http\Controllers\Upwork\WebhookController;

Route::group(['prefix' => 'upwork'], function () {
    Route::get('/login', [AuthController::class, 'upworkLoginPage'])->name('upwork.login.get');
    Route::post('/login', [AuthController::class, 'upworkLoginPost'])->name('upwork.login.post');
    Route::get('/forgot-password', [AuthController::class, 'upworkForgotPage'])->name('upwork.forgot.get');
    Route::get('/reset/{token?}/password', [AuthController::class, 'upworkResetPage'])->name('upwork.reset.get');
    Route::post('/forgot-password', [AuthController::class, 'upworkForgotPost'])->name('upwork.forgot.post');
    Route::post('/reset-password', [AuthController::class, 'upworkResetPost'])->name('upwork.reset.post');
    Route::get('/logout', [AuthController::class, 'upworklogout'])->name('upwork.logout');

    Route::group(['middleware' => ['membersOnline', 'upwork', 'tenant.feature:upwork_module']], function () {
        Route::get('/dashboard', [ViewsController::class, 'upworkDashboard'])->name('upwork.index.get');

        Route::get('/clients', [ViewsController::class, 'upworkClients'])->name('upwork.clients.get');
        Route::post('/client-delete', [CrudController::class, 'deleteClient'])->name('upwork.client.delete');
        Route::post('/client-status', [CrudController::class, 'updateClientStatus'])->name('upwork.client.updateStatus');
        Route::post('/client-account-access', [CrudController::class, 'clientAccountAccess'])->name('upwork.client.account-access');

        Route::get('/orders', [OrdersController::class, 'upworkOrders'])->name('upwork.orders.get');
        Route::get('/payments', [OrdersController::class, 'upworkPayments'])->name('upwork.payments.get');
        Route::post('/order/{id?}/delete', [CrudController::class, 'deleteOrder'])->name('upwork.delete.order');

        Route::get('/link-generator', [OrdersController::class, 'upworklinkGenerator'])->name('upwork.link-generator.get');
        Route::get('/generate/{order}/installment', [OrdersController::class, 'upworklinkGeneratorFinal'])
            ->whereNumber('order')
            ->name('upwork.link-generator.installment');
        Route::post('/generate-link', [OrdersController::class, 'generatePayLinkFirst'])->name('upwork.generate-payment-link');
        Route::post('/generate-installment/{order}/link', [OrdersController::class, 'generatePayLinkInstallment'])
            ->whereNumber('order')
            ->name('upwork.link-generator.final');

        Route::get('/generate/{order?}/invoice', [ViewsController::class, 'generateInvoice'])
            ->whereNumber('order')
            ->name('upwork.order.generate-invoice');
    });
});

Route::prefix('upwork-pay')->name('upwork.paylinks.')->group(function () {
    Route::get('/now/{token}', [WebhookController::class, 'showUpworkPaymentPage'])->name('show');
    Route::post('/now/{token}/checkout', [WebhookController::class, 'createCheckout'])->name('checkout');
    Route::get('/now/{token}/success', [WebhookController::class, 'checkoutSuccess'])->name('success');
    Route::get('/now/{token}/cancel', [WebhookController::class, 'checkoutCancel'])->name('cancel');
    Route::get('/now/{token}/error', [WebhookController::class, 'checkoutError'])->name('error');
});
