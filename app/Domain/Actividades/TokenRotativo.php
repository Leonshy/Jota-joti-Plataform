<?php

namespace App\Domain\Actividades;

use App\Models\SesionAsistencia;
use Carbon\CarbonInterface;

/**
 * Token del QR de asistencia = HMAC(secreto de la sesión, ventana de 60 s) (§5.4.7).
 * Se acepta la ventana actual y, durante los primeros 10 s de una ventana nueva, también la
 * anterior (gracia por latencia).
 */
class TokenRotativo
{
    public const VENTANA = 60;

    public const GRACIA = 10;

    public function generar(SesionAsistencia $sesion, ?CarbonInterface $ahora = null): string
    {
        return $this->deVentana($sesion, $this->ventana($ahora ?? now()));
    }

    public function esValido(SesionAsistencia $sesion, string $token, ?CarbonInterface $ahora = null): bool
    {
        $ahora ??= now();
        $ventana = $this->ventana($ahora);

        $actual = hash_equals($this->deVentana($sesion, $ventana), $token);
        $anterior = $this->segundosEnVentana($ahora) < self::GRACIA
            && hash_equals($this->deVentana($sesion, $ventana - 1), $token);

        return $actual || $anterior;
    }

    public function segundosRestantes(?CarbonInterface $ahora = null): int
    {
        return self::VENTANA - $this->segundosEnVentana($ahora ?? now());
    }

    private function ventana(CarbonInterface $t): int
    {
        return intdiv($t->getTimestamp(), self::VENTANA);
    }

    private function segundosEnVentana(CarbonInterface $t): int
    {
        return $t->getTimestamp() % self::VENTANA;
    }

    private function deVentana(SesionAsistencia $sesion, int $ventana): string
    {
        return substr(hash_hmac('sha256', "{$sesion->getKey()}:{$ventana}", $sesion->secreto), 0, 32);
    }
}
