<?php

use App\Http\Controllers\Tenant\ContactQueryController;
use App\Http\Controllers\Central\DemoRequestController;
use App\Http\Controllers\Central\StripeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontViews\LandingPagesController;
use App\Http\Controllers\FrontViews\ViewsController as FrontViewsController;
use App\Http\Controllers\FrontViews\SeoController;


// Legacy landing pages → canonical home (avoid duplicate content)
Route::redirect('/crm', '/', 301);
Route::redirect('/nexus', '/', 301);

Route::get('/', function () {
    return view('front.pages.index');
})->name('index.get');


// front views
Route::get('/contact-us', [FrontViewsController::class, 'showContactPage'])->name('contact-us.get');
Route::get('/pricing', [FrontViewsController::class, 'showPricingPage'])->name('pricing.get');
Route::get('/features', [FrontViewsController::class, 'showFeaturesPage'])->name('features.get');
Route::get('/about', [FrontViewsController::class, 'showAboutPage'])->name('about.get');
Route::get('/faq', [FrontViewsController::class, 'showFaqPage'])->name('faq.get');
Route::get('/terms', [FrontViewsController::class, 'showTermsPage'])->name('terms.get');
Route::get('/privacy-policy', [FrontViewsController::class, 'showPrivacyPage'])->name('privacy.get');

// Paid-ads landing pages (focused CTAs — use these as campaign destinations)
Route::get('/lp/agency-crm-trial', [LandingPagesController::class, 'trial'])->name('lp.trial');
Route::get('/lp/demo', [LandingPagesController::class, 'demo'])->name('lp.demo');
Route::get('/lp/demo/thanks', [LandingPagesController::class, 'demoThanks'])->name('lp.demo.thanks');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::post('/contact', [ContactQueryController::class, 'storeContactQuery'])
    ->name('contact.store');

Route::post('/request-demo', [DemoRequestController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('demo.store');

Route::get('/renew/approve/{token}', [StripeController::class, 'approveRenewal'])
    ->name('super-renew.approve');

// // admin views -- toggle mode
// Route::get('/web-drop', [ManagementController::class, 'toggleSettingDown'])->name('site.down');
// Route::get('/web-pick', [ManagementController::class, 'toggleSettingUp'])->name('site.up');
