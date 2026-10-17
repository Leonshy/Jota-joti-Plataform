<?php

namespace App\Domain\Contactos;

use App\Domain\Ledger\AcreditarPatrulla;
use App\Domain\Ledger\AcreditarUnidad;
use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Enums\EstadoEntrega;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Aprobar acredita el puntaje que define el admin para ese tipo de contacto (local o del exterior), con la tabla de §4.5:
 *  - JOTI: +P puntos y +P LisCoins a la persona
 *  - JOTA: +P puntos a la patrulla (una vez) y +⌈P/n⌉ LisCoins a cada integrante; en los Rovers, cada Rover recibe +P y +P
 * Se aprueba una sola vez: la fila se bloquea y, si ya fue revisada, quien llega segundo ve «ya revisado».
 */
class AprobarContacto
{
    public function __construct(
        private Puntaje $puntaje,
        private Puntos $puntos,
        private Jlis $jlis,
        private AcreditarPatrulla $acreditarPatrulla,
        private AcreditarUnidad $acreditarUnidad,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ContactoYaRevisado
     * @throws PuntajeSinConfigurar
     */
    public function __invoke(User $revisor, Contacto $contacto): Contacto
    {
        return DB::transaction(function () use ($revisor, $contacto) {
            $actual = Contacto::query()->with(['user', 'patrulla', 'unidad'])->whereKey($contacto->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($revisor)->authorize('revisar', $actual);

            if ($actual->estado !== EstadoEntrega::Pendiente) {
                throw new ContactoYaRevisado($actual->estado);
            }

            $puntos = $this->puntaje->para($actual->tipo, $actual->es_local) ?? throw new PuntajeSinConfigurar;

            $actual->forceFill([
                'estado' => EstadoEntrega::Aprobada,
                'puntos' => $puntos,
                'revisado_por' => $revisor->getKey(),
                'revisado_at' => now(),
            ])->save();

            if ($puntos > 0) {
                $this->acreditar($actual, $puntos, $revisor);
            }

            return $actual;
        });
    }

    private function acreditar(Contacto $c, int $puntos, User $revisor): void
    {
        $motivo = 'Contacto '.$c->tipo->getLabel().' aprobado'.($c->es_local ? ' (local)' : ' (exterior)');

        if (! $c->esJota()) {
            $this->puntos->individual($c->user, $puntos, $c, $motivo, $revisor);
            $this->jlis->acreditar($c->user, $puntos, $c, $motivo, $revisor);

            return;
        }

        $c->patrulla
            ? ($this->acreditarPatrulla)($c->patrulla, $puntos, $c, $motivo, $revisor)
            : ($this->acreditarUnidad)($c->unidad, $puntos, $c, $motivo, $revisor);
    }
}
