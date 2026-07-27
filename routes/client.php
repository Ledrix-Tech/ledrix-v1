<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\Client\BriefController as ClientBriefController;
use App\Http\Controllers\API\Client\AuthController as ClientAuthController;
use App\Http\Controllers\API\Client\LeadsController as ClientLeadsController;
use App\Http\Controllers\API\Client\PagesController as ClientPagesController;

Route::group(['prefix' => 'client', 'namespace' => 'Client'], function () {
    Route::get('/login', [ClientAuthController::class, 'clientLoginPage'])->name('client.login.get');
    Route::post('/login', [ClientAuthController::class, 'clientLoginPost'])
        ->middleware('throttle:10,1')
        ->name('client.login.post');
    Route::get('/forgot-password', [ClientAuthController::class, 'clientForgotPage'])->name('client.forgot.get');
    Route::get('/reset/{token?}/password', [ClientAuthController::class, 'clientResetPage'])->name('client.reset.get');
    Route::post('/forgot-password', [ClientAuthController::class, 'clientForgotPost'])
        ->middleware('throttle:5,1')
        ->name('client.forgot.post');
    Route::post('/reset-password', [ClientAuthController::class, 'clientResetPost'])
        ->middleware('throttle:10,1')
        ->name('client.reset.post');
    Route::post('/logout', [ClientAuthController::class, 'clientlogout'])->name('client.logout');

    Route::get('/brief/{token?}', [ClientBriefController::class, 'showBriefForm'])->name('brief.show');
    Route::post('/brief/{token?}', [ClientBriefController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('brief.submit');

    Route::group(['middleware' => 'client'], function () {
        Route::get('/dashboard', [ClientPagesController::class, 'clientIndex'])->name('client.index.get');
        Route::get('/messages', [ClientPagesController::class, 'clientMessages'])->name('client.messages.get');
        Route::get('/invoices', [ClientPagesController::class, 'clientInvoices'])->name('client.invoice.get');
        Route::get('/invoice/{order}/details', [ClientPagesController::class, 'clientInvoiceDetails'])
            ->whereNumber('order')
            ->name('client.invoice.details');
        Route::get('/profile', [ClientPagesController::class, 'clientProfile'])->name('client.profile.get');
        Route::post('/profile-update', [ClientAuthController::class, 'updateProfile'])->name('client.profile.update');

        Route::get('/raise/{order}/ticket', [ClientLeadsController::class, 'clientRaiseTicket'])
            ->whereNumber('order')
            ->name('client.raise-ticket.get');
        Route::get('/raised-tickets', [ClientLeadsController::class, 'clientTickets'])->name('client.raised-tickets.get');
        Route::post('/raised-ticket', [ClientLeadsController::class, 'clientTicketStore'])->name('client.raised-tickets.post');

        Route::get('/briefs', [ClientBriefController::class, 'clientBriefs'])->name('client.brief.get');
        Route::post('/brief-form', [ClientBriefController::class, 'clientBriefPost'])->name('client.brief-form.post');
    });
});
