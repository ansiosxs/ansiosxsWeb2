<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
    'allowed_origins' => [
        env('FRONTEND_URL', env('APP_ENV') === 'local'
            ? 'http://127.0.0.1:8002'
            : 'https://www.ansiosxs.cl'),
    ],
    'allowed_origins_patterns' => env('APP_ENV') === 'local'
        ? ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],
    'allowed_headers' => ['Accept', 'Content-Type', 'Origin'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
