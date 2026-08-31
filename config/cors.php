<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list of allowed site origins, e.g.
    // "https://abbadev.com,https://www.abbadev.com". Defaults are dev + prod.
    'allowed_origins' => array_filter(explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:5173,https://abbadev.com,https://www.abbadev.com',
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
