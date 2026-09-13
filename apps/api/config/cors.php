<?php

$defaultOrigins = 'http://localhost:5173,http://127.0.0.1:5173';
$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', $defaultOrigins))
)));

if (in_array('*', $allowedOrigins, true)) {
    throw new RuntimeException(
        'CORS_ALLOWED_ORIGINS must not contain * because credentialed CORS is enabled.'
    );
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['ETag', 'Retry-After', 'Content-Disposition'],
    'max_age' => 600,
    'supports_credentials' => true,
];
