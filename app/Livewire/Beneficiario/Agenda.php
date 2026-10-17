<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Actividades\AgendaDelParticipante;
use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\EquipoDeReserva;
use App\Domain\Actividades\EstacionEnCurso;
use App\Domain\Actividades\ReservaDuplicada;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\SinPatrulla;
use App\Domain\Actividades\TallerYaApuntado;
use App\Domain\Actividades\TurnoLleno;
use App\Domain\Actividades\TurnoYaComenzo;
use App\Domain\Actividades\TurnoYaTermino;
use App\Models\Turno;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Actividades de la unidad con sus turnos. Reservar la decide siempre el servidor. */
#[Layout('components.layouts.movil')]
class Agenda extends Component
{
    /** exito | aviso */
    public string $tono = '';

    public string $mensaje = '';

    public function reservar(int $turnoId, Reservar $reservar): void
    {
        $this->intentar($turnoId, $reservar);

        // El aviso está arriba: se sube para que se vea aunque se haya tocado un botón de más abajo.
        $this->js("window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })");
    }

    private function intentar(int $turnoId, Reservar $reservar): void
    {
        $this->tono = 'aviso';
        $turno = Turno::query()->with('actividad')->find($turnoId);

        if (! $turno) {
            $this->mensaje = __('jota.actividades.ya_empezo');

            return;
        }

        $user = auth()->user();

        try {
            $reservar($user, $turno);
            $this->tono = 'exito';
            $clave = $turno->actividad->esEstacion() ? 'reservado_texto' : ($turno->actividad->esPorEquipo() ? 'apuntado_texto_equipo' : 'apuntado_texto');
            $this->mensaje = __('jota.actividades.'.$clave, ['actividad' => $turno->actividad->titulo, 'cuando' => cuando($turno->inicio)]);
        } catch (TurnoLleno) {
            $this->mensaje = __('jota.actividades.error_lleno');
        } catch (TallerYaApuntado $e) {
            $this->mensaje = __($e->porEquipo ? 'jota.actividades.error_taller_ya_equipo' : 'jota.actividades.error_taller_ya', ['actividad' => $e->actividad]);
        } catch (ReservaDuplicada) {
            $this->mensaje = __('jota.actividades.error_duplicada');
        } catch (SinPatrulla) {
            $this->mensaje = __('jota.actividades.error_sin_patrulla');
        } catch (TurnoYaComenzo) {
            $this->mensaje = __('jota.actividades.ya_empezo');
        } catch (TurnoYaTermino) {
            $this->mensaje = __('jota.actividades.ya_termino');
        } catch (EstacionEnCurso $e) {
            $this->mensaje = __('jota.actividades.error_estacion_en_curso', ['actividad' => $e->actividad, 'inicio' => $e->inicio->format('H:i'), 'fin' => $e->fin->format('H:i')]);
        } catch (ChoqueDeHorario $e) {
            $c = $e->choques[0];
            $propio = $c['quien'] === $user->name;
            $this->mensaje = __($propio ? 'jota.actividades.error_choque_propio' : 'jota.actividades.error_choque', [
                'actividad' => $c['actividad'],
                'quien' => $c['quien'],
                'inicio' => $c['inicio']->format('H:i'),
                'fin' => $c['fin']->format('H:i'),
            ]);
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function render(AgendaDelParticipante $agenda)
    {
        $user = auth()->user();

        // El equipo es la patrulla o, en las unidades que no se arman por patrulla (Rovers), toda la unidad.
        $equipo = $user->patrulla()->value('nombre')
            ?? (EquipoDeReserva::unidadEsEquipo($user->unidad_id) ? $user->unidad()->value('nombre') : null);

        $actividades = $agenda->actividades($user);

        return view('livewire.beneficiario.agenda', [
            'actividades' => $actividades,
            // Dos grupos bien separados, ya ordenados por el dominio.
            'talleres' => array_values(array_filter($actividades, fn ($a) => $a['taller'])),
            'estaciones' => array_values(array_filter($actividades, fn ($a) => ! $a['taller'])),
            'patrulla' => $equipo,
            'estacion' => $agenda->estacionReservada($user),
        ])->title(__('jota.navegacion.actividades'));
    }
}
