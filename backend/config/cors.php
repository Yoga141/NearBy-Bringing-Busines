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

    // The Vite dev server is only allowed outside production; FRONTEND_URL
    // may list several origins, comma-separated.
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Lets a cross-origin SPA read the file name of an Excel download.
    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => false,
];
