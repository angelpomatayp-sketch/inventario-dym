<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_merge(
        // Desarrollo local
        [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:5175',
            'http://localhost:5176',
            'http://localhost:5177',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5176',
            'http://localhost:3000',
        ],
        // Producción: Railway u otro dominio (configurar via FRONTEND_URL en .env)
        env('FRONTEND_URL') ? [env('FRONTEND_URL')] : [],
        env('FRONTEND_URL_2') ? [env('FRONTEND_URL_2')] : []
    )),

    'allowed_origins_patterns' => [
        // Solo en local (en producción FRONTEND_URL reemplaza esto)
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
        // Railway domains (https://nombre.up.railway.app)
        '#^https://[a-zA-Z0-9\-]+\.up\.railway\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
