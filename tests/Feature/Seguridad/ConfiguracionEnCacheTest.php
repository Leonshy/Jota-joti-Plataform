<?php

// Con `php artisan config:cache` el .env deja de leerse y env() devuelve null fuera de config/.
// Esta regla evita que vuelva a pasar (ya pasó con TRUSTED_PROXIES y APP_ENV en bootstrap/app.php).
it('no usa env() fuera de config/', function () {
    $archivos = collect([base_path('bootstrap/app.php')])
        ->merge(collect(['app', 'routes', 'database/seeders'])->flatMap(
            fn ($dir) => iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir), FilesystemIterator::SKIP_DOTS)))
        )->map->getPathname()->filter(fn ($f) => str_ends_with($f, '.php')));

    $usan = $archivos->filter(fn ($f) => preg_match('/\benv\(\s*[\'"]/', file_get_contents($f)))
        ->map(fn ($f) => str_replace(base_path().'/', '', $f))->values()->all();

    expect($usan)->toBe([]);
});

it('los proxies y hosts de confianza salen de config', function () {
    expect(config('jota'))->toHaveKeys(['trusted_proxies', 'trusted_hosts']);
});
