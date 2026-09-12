<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Allows the NearBy Vue frontend to call this API. Token-based auth is
    | used, so credentials support is not required. In production the
    | frontend is normally served from the same domain (see .cpanel.yml,
    | which deploys the API under /api on the same host as the built SPA),
    | so cross-origin requests may not even occur there. FRONTEND_URL lets
    | you add an extra allowed origin (e.g. a separate frontend domain)
    | without touching this file.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        env('FRONTEND_URL'),
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
