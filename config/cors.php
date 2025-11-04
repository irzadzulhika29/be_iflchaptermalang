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

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:5173',
        'http://localhost:8000',
        'http://127.0.0.1',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8000',
        'https://localhost',
        'https://localhost:8000',
        'https://*.ngrok.app',
        'https://*.ngrok-free.app',
        'https://*.ngrok.io'
    ],

    'allowed_origins_patterns' => [
        'https://.*\\.ngrok\\.io',
        'https://.*\\.ngrok-free\\.app',
        'https://.*\\.ngrok\\.app',
        'http://localhost:[0-9]+',
        'http://127.0.0.1:[0-9]+'
    ],

    'allowed_headers' => ['*', 'ngrok-skip-browser-warning'],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma'
    ],

    'max_age' => 60 * 60 * 24,  // 24 hours

    'supports_credentials' => true,

];