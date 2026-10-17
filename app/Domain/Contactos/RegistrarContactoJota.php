<?php

namespace App\Domain\Contactos;

use App\Domain\Actividades\EquipoDeReserva;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Enums\TipoContacto;
use App\Models\Contacto;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Contacto JOTA (radio): una fila del libro de guardia. Suma puntos a la PATRULLA (o, en los Rovers, a toda la unidad) y es
 * local si el indicativo es de Paraguay (ZP…). Cualquier integrante del equipo lo carga. No pide captura: los datos del libro
 * de guardia son los que valida el dirigente.
 */
class RegistrarContactoJota
{
    public const EMISIONES = ['LSB', 'USB', 'CW', 'AM', 'FM', 'FT8', 'FT4', 'RTTY', 'PSK31', 'SSTV', 'Otro'];

    /**
     * @param  array{contactado_at?: string, estacion?: string, rst_tx?: string, rst_rx?: string, frecuencia?: string, emision?: string, obs?: string|null, qsl_r?: bool, qsl_e?: bool}  $datos
     *
     * @throws AuthorizationException
     * @throws ContactoInvalido
     */
    public function __invoke(User $user, array $datos): Contacto
    {
        if ($user->rol !== Rol::Beneficiario || $user->unidad_id === null) {
            throw new AuthorizationException;
        }

        $equipoPorUnidad = ! $user->patrulla_id && EquipoDeReserva::unidadEsEquipo($user->unidad_id);

        if (! $user->patrulla_id && ! $equipoPorUnidad) {
            throw new ContactoInvalido('Los contactos de JOTA suman a tu patrulla y no estás en ninguna. Hablá con un dirigente.');
        }

        $d = $this->validar($datos);

        return DB::transaction(function () use ($user, $d, $equipoPorUnidad) {
            // Se bloquea al equipo para que dos integrantes que cargan el mismo contacto a la vez no lo dupliquen.
            $equipoPorUnidad
                ? Unidad::query()->whereKey($user->unidad_id)->lockForUpdate()->firstOrFail()
                : Patrulla::query()->whereKey($user->patrulla_id)->lockForUpdate()->firstOrFail();

            $repetido = Contacto::query()
                ->where('tipo', TipoContacto::Jota)
                ->where('estacion', $d['estacion'])
                ->where('contactado_at', $d['contactado_at'])
                ->where('estado', '!=', EstadoEntrega::Rechazada)
                ->when($equipoPorUnidad,
                    fn ($q) => $q->where('unidad_id', $user->unidad_id)->whereNull('patrulla_id'),
                    fn ($q) => $q->where('patrulla_id', $user->patrulla_id))
                ->exists();

            if ($repetido) {
                throw new ContactoInvalido('Ese contacto ya está cargado: misma estación, fecha y hora.');
            }

            return Contacto::create($d + [
                'tipo' => TipoContacto::Jota,
                'user_id' => $user->getKey(),
                'unidad_id' => $user->unidad_id,
                'patrulla_id' => $equipoPorUnidad ? null : $user->patrulla_id,
                'es_local' => Procedencia::esIndicativoLocal($d['estacion']),
                'estado' => EstadoEntrega::Pendiente,
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function validar(array $datos): array
    {
        $texto = trim((string) ($datos['contactado_at'] ?? ''));

        try {
            $cuando = $texto === '' ? throw new \InvalidArgumentException : Carbon::parse($texto, config('app.timezone'));
        } catch (\Throwable) {
            throw new ContactoInvalido('Indicá la fecha y la hora del contacto.');
        }

        if ($cuando->gt(now()->addMinutes(5))) {
            throw new ContactoInvalido('La fecha y la hora del contacto no pueden ser futuras.');
        }

        $estacion = Procedencia::indicativo((string) ($datos['estacion'] ?? ''));
        if (! preg_match('/^[A-Z0-9]{1,3}[0-9][A-Z]{1,4}(\/[A-Z0-9]{1,4})?$/', $estacion)) {
            throw new ContactoInvalido('El indicativo de la estación no parece válido (por ejemplo, ZP5ZDM).');
        }

        $frecuencia = str_replace(',', '.', trim((string) ($datos['frecuencia'] ?? '')));
        if (! preg_match('/^\d{1,6}(\.\d{1,6})?$/', $frecuencia) || (float) $frecuencia <= 0) {
            throw new ContactoInvalido('La frecuencia tiene que ser un número en MHz, por ejemplo 7.125.');
        }

        $emision = (string) ($datos['emision'] ?? '');
        if (! in_array($emision, self::EMISIONES, true)) {
            throw new ContactoInvalido('Elegí el modo de emisión.');
        }

        $obs = trim((string) preg_replace('/\s+/u', ' ', (string) ($datos['obs'] ?? '')));
        if (mb_strlen($obs) > 120) {
            throw new ContactoInvalido('Las observaciones pueden tener hasta 120 caracteres.');
        }

        return [
            'contactado_at' => $cuando->format('Y-m-d H:i:00'),
            'estacion' => $estacion,
            'rst_tx' => $this->rst((string) ($datos['rst_tx'] ?? ''), 'de emisión'),
            'rst_rx' => $this->rst((string) ($datos['rst_rx'] ?? ''), 'de recepción'),
            'frecuencia' => str_contains($frecuencia, '.') ? rtrim(rtrim($frecuencia, '0'), '.') : $frecuencia,
            'emision' => $emision,
            'obs' => $obs === '' ? null : $obs,
            'qsl_r' => (bool) ($datos['qsl_r'] ?? false),
            'qsl_e' => (bool) ($datos['qsl_e'] ?? false),
        ];
    }

    /** RST: legibilidad 1-5, intensidad 1-9 y, en telegrafía, tono 1-9 («59», «5-9», «599»). Se guarda con guiones: 5-9. */
    private function rst(string $valor, string $nombre): string
    {
        $digitos = preg_replace('/[\s-]/', '', $valor);

        if (! preg_match('/^[1-5][1-9][1-9]?$/', (string) $digitos)) {
            throw new ContactoInvalido("El RST {$nombre} no es válido (por ejemplo, 5-9).");
        }

        return implode('-', str_split($digitos));
    }
}
