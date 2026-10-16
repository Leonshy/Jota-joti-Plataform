<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En producción todo se genera con https (QR, enlaces firmados, redirecciones), aunque el proxy hable http.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->limitadores();
    }

    /**
     * Límites de uso. Se cuentan por PERSONA y no por IP: cientos de participantes comparten la misma red del evento
     * y un límite por IP los bloquearía a todos. El ingreso (CI + PIN) NO tiene límite: decisión de producto (R3, D8).
     */
    private function limitadores(): void
    {
        $porPersona = fn (Request $r) => $r->user()?->getAuthIdentifier() ?: $r->ip();

        // Escanear y pagar: una persona normal hace pocos por minuto; esto frena un script, no a una persona apurada.
        RateLimiter::for('escaneos', fn (Request $r) => Limit::perMinute(40)->by($porPersona($r)));
        RateLimiter::for('pagos', fn (Request $r) => Limit::perMinute(40)->by($porPersona($r)));

        // Pantalla pública del QR de asistencia: la consulta una sola pantalla, una vez por minuto.
        // (El límite corre antes de resolver el modelo: el parámetro de la ruta puede ser todavía el id.)
        RateLimiter::for('qr-publico', function (Request $r) {
            $sesion = $r->route('sesion');

            return Limit::perMinute(30)->by('qr:'.(is_object($sesion) ? $sesion->getKey() : ($sesion ?? $r->ip())));
        });
    }
}
