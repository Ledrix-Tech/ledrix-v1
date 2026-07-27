<?php

namespace App\Providers;

use App\Services\PayPalGateway;
use App\Services\StripeGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            StripeGateway::class,
            fn () => new StripeGateway(config('services.stripe.secret'))
        );

        $this->app->bind(PayPalGateway::class, function () {
            return new PayPalGateway([
                'client_id'   => config('services.paypal.client_id'),
                'secret'      => config('services.paypal.secret'),
                'base'        => config('services.paypal.base_url', 'https://api.paypal.com'),
                'webhook_id'  => config('services.paypal.webhook_id'),
            ]);
        });

        $this->app->singleton(PaymentGatewayFactory::class);
    }
    // app/Providers/AuthServiceProvider.php
    protected $policies = [
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        View::composer(['admin.*', 'sellers.*'], function ($view) {
            $view->with('authAdmin', Auth::guard('admin')->user());
        });

        View::composer('sellers.*', function ($view) {
            if (! function_exists('tenantFeatures')) {
                return;
            }

            $features = tenantFeatures();
            $view->with('tenantFeatures', $features);
            $view->with('tenantHasPayments', tenantHasPayments());
            $view->with('showLeadPrediction', tenantFeature('lead_prediction'));
            $view->with('tenantHasClientPortal', tenantFeature('client_portal'));
            $view->with('tenantHasSupportTickets', tenantFeature('support_tickets'));
            $view->with('tenantHasMilestonePayments', tenantFeature('milestone_payments'));
            $view->with('tenantHasStripe', tenantFeature('stripe'));
            $view->with('tenantHasPayPal', tenantFeature('paypal'));
            $view->with('tenantHasApiAccess', tenantFeature('api_access'));
            $view->with('tenantHasDualInvoicing', tenantFeature('dual_invoicing'));
            $view->with('tenantHasSellerLeaderboard', tenantFeature('seller_leaderboard'));
        });

        // dynamic route prfix changed
        // $url = app('url');
        // $url->macro('route', function ($name, $parameters = [], $absolute = true) use ($url) {
        //     $guardPrefix = auth('seller')->check() ? 'seller' : 'admin';

        //     if (!str_starts_with($name, 'admin.') && !str_starts_with($name, 'seller.')) {
        //         $name = "{$guardPrefix}.{$name}";
        //     }

        //     return $url->to(route($name, $parameters, $absolute));
        // });
    }
}
