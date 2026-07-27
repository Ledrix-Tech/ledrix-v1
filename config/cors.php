<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'], // allow all HTTP methods
    'allowed_origins' => ['*'], // whitelist your domain
    'allowed_origins_patterns' => [], // not needed
    'allowed_headers' => ['*'], // allow all headers
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false, // keep false unless you use cookies/sessions
];


// // config/cors.php
// return [
//     'paths' => ['api/*'],
//     'allowed_methods' => ['POST', 'OPTIONS'],
//     // during dev, allow localhost on any port:
//     'allowed_origins' => ['*'],
//     'allowed_origins_patterns' => ['#^http://localhost(:\d+)?$#', '#^http://127\.0\.0\.1(:\d+)?$#'],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 3600,
//     'supports_credentials' => false,
// ];
