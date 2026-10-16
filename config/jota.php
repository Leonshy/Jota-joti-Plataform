<?php

return [
    'tienda' => [
        // El token del cobro es de un solo uso y vence a los 2 minutos (§5.4.8).
        'vigencia_segundos' => 120,
        'monto_maximo' => 1_000_000,
    ],

    // Detrás de nginx (Plesk). Se leen acá y no con env() en bootstrap/app.php: con `config:cache` el .env deja de leerse.
    'trusted_proxies' => env('TRUSTED_PROXIES'),
    'trusted_hosts' => env('TRUSTED_HOSTS', ''),

    // Solo para DatabaseSeeder (entornos de prueba). Ver .env.example.
    'seed_pin' => env('SEED_PIN', '1234'),
];
