<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:4200',
        'http://localhost:8100',
        'capacitor://localhost',
        'ionic://localhost',
        'http://localhost',
        'http://10.7.218.154:8000',
        'http://10.0.2.2:8000',
        env('FRONTEND_URL', 'http://localhost:4200'),
    ],
    'allowed_origins_patterns' => [
        '/^https?:\/\/.*\.railway\.app$/',  // 🔥 Permitir todos los subdominios de Railway
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
