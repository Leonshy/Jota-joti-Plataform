<?php

namespace App\Domain\Tienda;

use App\Models\Cobro;
use App\Models\Devolucion;
use App\Models\User;

/** Lo que una persona gastó en la tienda y lo que ya se le devolvió: de ahí sale el tope de una devolución. */
class ResumenCompras
{
    /** @return array{gastado: int, devuelto: int, maximo: int} */
    public function __invoke(User $participante): array
    {
        $gastado = (int) Cobro::query()->where('pagado_por', $participante->getKey())->whereNotNull('pagado_at')->sum('monto');
        $devuelto = (int) Devolucion::query()->where('user_id', $participante->getKey())->sum('monto');

        return ['gastado' => $gastado, 'devuelto' => $devuelto, 'maximo' => max(0, $gastado - $devuelto)];
    }
}
