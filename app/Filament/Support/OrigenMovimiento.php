<?php

namespace App\Filament\Support;

use App\Models\Asistencia;
use App\Models\Cobro;
use App\Models\CumplimientoEquipo;
use App\Models\Devolucion;
use App\Models\Entrega;
use App\Models\Penalizacion;

/** Texto en español del "por qué" de un movimiento, a partir de su origen. */
class OrigenMovimiento
{
    public static function etiqueta(?string $tipo, ?int $id): string
    {
        $nombre = match ($tipo) {
            Entrega::class => 'Misión aprobada',
            Asistencia::class => 'Asistencia a actividad',
            CumplimientoEquipo::class => 'Actividad por equipo cumplida',
            Cobro::class => 'Compra en la tienda',
            Devolucion::class => 'Devolución de la tienda',
            Penalizacion::class => 'Penalización',
            null => 'Ajuste manual',
            default => class_basename($tipo),
        };

        return $id ? "{$nombre} #{$id}" : $nombre;
    }
}
