<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_DEBUG', false)
        ? ['http://localhost:50080', 'http://org.tickets.loc:50080']
        : explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,

];
