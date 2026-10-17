<?php

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\AsistenciaYaRegistrada;
use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\CumplimientoYaAcreditado;
use App\Domain\Actividades\MarcarCumplimiento;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\ReservaDuplicada;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\SesionYaAbierta;
use App\Domain\Actividades\TallerYaApuntado;
use App\Domain\Actividades\TurnoLleno;
use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\ContactoInvalido;
use App\Domain\Contactos\ContactoYaRevisado;
use App\Domain\Contactos\RegistrarContactoJota;
use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Domain\Ledger\SaldoInsuficiente;
use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregaBloqueada;
use App\Domain\Misiones\EntregarMision;
use App\Domain\Misiones\EntregaYaRevisada;
use App\Domain\Penalizaciones\AnularPenalizacion;
use App\Domain\Penalizaciones\PenalizacionYaAnulada;
use App\Domain\Penalizaciones\Penalizar;
use App\Domain\Tienda\CobroVencido;
use App\Domain\Tienda\CobroYaPagado;
use App\Domain\Tienda\DevolucionExcedeCompras;
use App\Domain\Tienda\Devolver;
use App\Domain\Tienda\PagarCobro;
use App\Models\Actividad;
use App\Models\Contacto;
use App\Models\Entrega;
use App\Models\Mision;
use App\Models\Patrulla;
use App\Models\Penalizacion;
use App\Models\SesionAsistencia;
use App\Models\Turno;
use App\Models\User;

/*
 * Closures para Concurrency::run(). Se crean en funciones globales y no dentro de los tests de
 * Pest: así no arrastran el scope de la clase del test, que el proceso hijo no puede reconstruir.
 */

function tareaAcreditarPuntos(int $userId, int $cantidad): Closure
{
    return static function () use ($userId, $cantidad) {
        app(Puntos::class)->individual(User::findOrFail($userId), $cantidad);

        return 'ok';
    };
}

function tareaDebitarJlis(int $userId, int $monto): Closure
{
    return static function () use ($userId, $monto) {
        try {
            app(Jlis::class)->debitar(User::findOrFail($userId), $monto);

            return 'ok';
        } catch (SaldoInsuficiente) {
            return 'rechazado';
        }
    };
}

function tareaAprobarEntrega(int $entregaId, int $revisorId): Closure
{
    return static function () use ($entregaId, $revisorId) {
        try {
            app(AprobarEntrega::class)(
                User::findOrFail($revisorId),
                Entrega::findOrFail($entregaId),
            );

            return 'ok';
        } catch (EntregaYaRevisada) {
            return 'ya revisada';
        }
    };
}

function tareaEntregarMision(int $userId, int $misionId): Closure
{
    return static function () use ($userId, $misionId) {
        try {
            app(EntregarMision::class)(
                User::findOrFail($userId),
                Mision::findOrFail($misionId),
                'entrega simultánea',
            );

            return 'ok';
        } catch (EntregaBloqueada) {
            return 'bloqueada';
        }
    };
}

function tareaReservar(int $userId, int $turnoId): Closure
{
    return static function () use ($userId, $turnoId) {
        try {
            app(Reservar::class)(User::findOrFail($userId), Turno::findOrFail($turnoId));

            return 'ok';
        } catch (TurnoLleno) {
            return 'lleno';
        } catch (ChoqueDeHorario) {
            return 'choque';
        } catch (ReservaDuplicada) {
            return 'duplicada';
        } catch (TallerYaApuntado) {
            return 'taller ya apuntado';
        }
    };
}

function tareaEscanear(int $userId, int $sesionId, string $token): Closure
{
    return static function () use ($userId, $sesionId, $token) {
        try {
            app(RegistrarAsistencia::class)(
                User::findOrFail($userId),
                SesionAsistencia::findOrFail($sesionId),
                $token,
            );

            return 'ok';
        } catch (AsistenciaYaRegistrada) {
            return 'ya registrada';
        }
    };
}

function tareaMarcarCumplida(int $marcadorId, int $actividadId, int $patrullaId): Closure
{
    return static function () use ($marcadorId, $actividadId, $patrullaId) {
        try {
            app(MarcarCumplimiento::class)(
                User::findOrFail($marcadorId),
                Actividad::findOrFail($actividadId),
                Patrulla::findOrFail($patrullaId),
                true,
            );

            return 'ok';
        } catch (CumplimientoYaAcreditado) {
            return 'ya acreditado';
        }
    };
}

function tareaAbrirSesion(int $dirigenteId, int $actividadId): Closure
{
    return static function () use ($dirigenteId, $actividadId) {
        try {
            app(AbrirSesionAsistencia::class)(
                User::findOrFail($dirigenteId),
                Actividad::findOrFail($actividadId),
            );

            return 'ok';
        } catch (SesionYaAbierta) {
            return 'ya abierta';
        }
    };
}

function tareaPagarCobro(int $userId, string $token): Closure
{
    return static function () use ($userId, $token) {
        try {
            app(PagarCobro::class)(User::findOrFail($userId), $token);

            return 'ok';
        } catch (CobroYaPagado) {
            return 'ya pagado';
        } catch (SaldoInsuficiente) {
            return 'saldo insuficiente';
        } catch (CobroVencido) {
            return 'vencido';
        }
    };
}

function tareaDevolver(int $tiendaId, int $userId, int $monto): Closure
{
    return static function () use ($tiendaId, $userId, $monto) {
        try {
            app(Devolver::class)(User::findOrFail($tiendaId), User::findOrFail($userId), $monto, 'Devolución simultánea');

            return 'ok';
        } catch (DevolucionExcedeCompras) {
            return 'excede';
        }
    };
}

function tareaPenalizarPersona(int $autorId, int $personaId, int $cantidad): Closure
{
    return static function () use ($autorId, $personaId, $cantidad) {
        app(Penalizar::class)->persona(User::findOrFail($autorId), User::findOrFail($personaId), $cantidad, 'Penalización simultánea');

        return 'ok';
    };
}

function tareaAnularPenalizacion(int $autorId, int $penalizacionId): Closure
{
    return static function () use ($autorId, $penalizacionId) {
        try {
            app(AnularPenalizacion::class)(User::findOrFail($autorId), Penalizacion::findOrFail($penalizacionId), 'Anulación simultánea');

            return 'ok';
        } catch (PenalizacionYaAnulada) {
            return 'ya anulada';
        }
    };
}

function tareaAprobarContacto(int $contactoId, int $revisorId): Closure
{
    return static function () use ($contactoId, $revisorId) {
        try {
            app(AprobarContacto::class)(User::findOrFail($revisorId), Contacto::findOrFail($contactoId));

            return 'ok';
        } catch (ContactoYaRevisado) {
            return 'ya revisado';
        }
    };
}

function tareaRegistrarJota(int $userId, array $datos): Closure
{
    return static function () use ($userId, $datos) {
        try {
            app(RegistrarContactoJota::class)(User::findOrFail($userId), $datos);

            return 'ok';
        } catch (ContactoInvalido) {
            return 'repetido';
        }
    };
}
