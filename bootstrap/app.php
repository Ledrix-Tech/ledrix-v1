<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/tenant.php',
            __DIR__ . '/../routes/admin.php',
            __DIR__ . '/../routes/super-admin.php',
            __DIR__ . '/../routes/upwork.php',
            __DIR__ . '/../routes/client.php',
            __DIR__ . '/../routes/seller.php',
            __DIR__ . '/../routes/compliance.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/web-pick',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'upwork' => \App\Http\Middleware\UpworkMiddleware::class,
            'seller' => \App\Http\Middleware\SellerMiddleware::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'membersOnline' => \App\Http\Middleware\UpdateLastSeen::class,
            'restrict' => \App\Http\Middleware\RestrictToMyIp::class,
            'admin_or_seller' => \App\Http\Middleware\AdminOrSeller::class,
            'admin.only' => \App\Http\Middleware\AdminOnlyMiddleware::class,
            'finance.restrict' => \App\Http\Middleware\FinanceRestrictedMiddleware::class,
            'portal.auth' => \App\Http\Middleware\PortalAuthMiddleware::class,
            'tenant.feature' => \App\Http\Middleware\EnsureTenantFeatureMiddleware::class,
            'portal.tenant.feature' => \App\Http\Middleware\EnsurePortalTenantFeatureMiddleware::class,
            'crm.workspace' => \App\Http\Middleware\EnsureCrmWorkspaceMiddleware::class,

            'super-admin' => \App\Http\Middleware\Central\SuperAdminMiddleware::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'tenant.context' => \App\Http\Middleware\SetTenantContext::class,
            'tenant.api' => \App\Http\Middleware\AuthenticateTenantApiToken::class,
        ]);

        // Global middleware
        $middleware->append(\Illuminate\Http\Middleware\TrustProxies::class);
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->append(\App\Http\Middleware\PreventRequestsDuringMaintenance::class);
        $middleware->append(\Illuminate\Foundation\Http\Middleware\ValidatePostSize::class);
        $middleware->append(\Illuminate\Foundation\Http\Middleware\TrimStrings::class);
        $middleware->append(\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);

        // Web group
        $middleware->group('web', [
            \App\Http\Middleware\NormalizeDevelopmentHost::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCSRFToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckSectionMaintenance::class,
            \App\Http\Middleware\SetTenantContext::class,

        ]);

        // API group (no session, CSRF)
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // $exceptions->render(function (Throwable $e, $request) {
        //     // 🔹 Database or query failure
        //     if ($e instanceof QueryException || $e instanceof PDOException) {
        //         $reason = 'Database connection or query error detected.';
        //         return response()->view('errors.503', compact('reason'), 503);
        //     }

        //     // 🔹 File or permission problem
        //     if ($e instanceof FileNotFoundException) {
        //         $reason = 'File system or storage permission issue.';
        //         return response()->view('errors.503', compact('reason'), 503);
        //     }

        //     // 🔹 Security / forbidden access
        //     if ($e instanceof HttpException && $e->getStatusCode() === 403) {
        //         $reason = 'Security restriction or unauthorized access.';
        //         return response()->view('errors.503', compact('reason'), 503);
        //     }

        //     // 🔹 Generic system error
        //     if ($e instanceof ErrorException) {
        //         $reason = 'General system error occurred.';
        //         return response()->view('errors.503', compact('reason'), 503);
        //     }
        // });
    })
    ->withProviders([
        App\Providers\PanelRoutingServiceProvider::class,
    ])
    ->create();
