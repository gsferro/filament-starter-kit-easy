<?php

return [
    'cache-prefix' => '',

    // Desligado por padrão: só faz sentido com Redis (docker). Ligue com
    // MODEL_CACHE_ENABLED=true + MODEL_CACHE_STORE=model-cache no .env.
    'enabled' => env('MODEL_CACHE_ENABLED', false),

    'use-database-keying' => env('MODEL_CACHE_USE_DATABASE_KEYING', true),

    'store' => env('MODEL_CACHE_STORE'),

    'fallback-to-database' => env('MODEL_CACHE_FALLBACK_TO_DB', false),
];
