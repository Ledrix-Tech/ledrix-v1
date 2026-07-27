<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PanelRoutingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Define global helper for role-based routes
        app('url')->macro('panelRoute', function ($name, $parameters = [], $absolute = true) {
            $guardPrefix = auth('seller')->check() ? 'seller' : 'admin';
            // If route name already includes guard, just return it
            if (str_starts_with($name, 'admin.') || str_starts_with($name, 'seller.')) {
                return route($name, $parameters, $absolute);
            }
            // Otherwise, inject correct prefix automatically
            return route("{$guardPrefix}.{$name}", $parameters, $absolute);
        });

        // Optional: add a Blade directive (if you prefer @panelRoute)
        Blade::directive('panelRoute', function ($expression) {
            return "<?php echo app('url')->panelRoute($expression); ?>";
        });
    }
}
