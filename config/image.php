<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para gerenciamento de imagens
    | Suporta múltiplos ambientes e CDN
    |
    */

    // Disco padrão para armazenamento de imagens
    'default_disk' => env('IMAGE_STORAGE_DISK', 'public'),

    // URL base para imagens (pode ser CDN)
    'cdn_url' => env('IMAGE_CDN_URL', null),

    // Cache de URLs de imagens
    'cache_urls' => env('IMAGE_CACHE_URLS', true),
    'cache_ttl' => env('IMAGE_CACHE_TTL', 86400), // 24 horas

    // Otimização de imagens
    'optimization' => [
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', false),
        'quality' => env('IMAGE_QUALITY', 80),
        'max_width' => env('IMAGE_MAX_WIDTH', 1920),
        'max_height' => env('IMAGE_MAX_HEIGHT', 1080),
    ],

    // Formatos permitidos
    'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp'],

    // Tamanho máximo (em bytes)
    'max_size' => 5 * 1024 * 1024, // 5MB

    // Dimensões mínimas
    'min_dimensions' => [
        'width' => 200,
        'height' => 200,
    ],
];

