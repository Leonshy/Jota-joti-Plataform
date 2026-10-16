<?php

use App\Domain\Acceso\DestinoPorRol;
use App\Http\Middleware\EncabezadosDeSeguridad;
use App\Http\Middleware\EnsureRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sin sesión: se va a Ingresar y, tras entrar, vuelve a donde iba (por ejemplo, al QR que escaneó).
        $middleware->redirectGuestsTo(fn () => route('ingresar'));
        // Con sesión, ingresar/activar no tienen sentido: va a su pantalla de inicio.
        $middleware->redirectUsersTo(fn () => DestinoPorRol::para(auth()->user()));
        $middleware->alias(['rol' => EnsureRol::class]);
        // Global (no solo del grupo «web»): así también alcanza al panel de administración y a las páginas de error.
        $middleware->append(EncabezadosDeSeguridad::class);

        // El rol se revisa ANTES de buscar el modelo de la ruta: quien no corresponde recibe 403 siempre, y no
        // puede descubrir qué números de entrega, sesión o actividad existen por la diferencia entre 403 y 404.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureRol::class);

        // En producción solo se atiende el dominio oficial: un encabezado Host falso no puede fabricar enlaces ni QR.
        // Todo se lee de config() y dentro del closure: con `config:cache` el .env ya no se lee y env() daría null.
        $middleware->trustHosts(at: fn () => app()->isProduction() ? array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            ...array_map('trim', explode(',', (string) config('jota.trusted_hosts'))),
        ]) : [], subdomains: false);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
