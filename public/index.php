<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Local dev: CRM pages can exceed default 512M (especially with debug tooling).
$envPath = __DIR__ . '/../.env';
if (is_readable($envPath) && preg_match('/^APP_ENV=local\s*$/m', (string) file_get_contents($envPath))) {
    ini_set('memory_limit', '1024M');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
