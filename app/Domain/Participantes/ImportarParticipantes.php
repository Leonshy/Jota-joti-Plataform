<?php

namespace App\Domain\Participantes;

use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Carga de participantes por CSV antes del evento (§4.2), con reporte de errores línea por línea.
 *
 * Todo o nada: si una sola línea tiene errores no se importa ninguna, así el padrón nunca queda
 * a medias. Con `$soloValidar` se prueba el archivo sin guardar nada. Los participantes quedan sin
 * PIN: cada uno lo crea con su CI en el primer ingreso. Las patrullas que no existen se crean.
 *
 * Columnas (el orden no importa; se aceptan variantes sin tildes): ci, nombre, grupo, unidad y,
 * opcionales, rol (beneficiario por defecto, dirigente o tienda) y patrulla.
 */
class ImportarParticipantes
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const MAX_FILAS = 5000;

    private const ALIAS = [
        'ci' => ['ci', 'cedula', 'cedulaidentidad', 'documento', 'nrocedula', 'nrodecedula', 'numerodecedula'],
        'nombre' => ['nombre', 'nombres', 'nombreyapellido', 'apellidoynombre', 'nombrecompleto', 'name'],
        'rol' => ['rol'],
        'grupo' => ['grupo', 'gruposcout'],
        'unidad' => ['unidad', 'rama', 'seccion'],
        'patrulla' => ['patrulla', 'equipo'],
    ];

    private const OBLIGATORIAS = ['ci', 'nombre', 'grupo', 'unidad'];

    private const ROLES_CSV = ['beneficiario' => Rol::Beneficiario, 'dirigente' => Rol::Dirigente, 'tienda' => Rol::Tienda];

    /**
     * @throws AuthorizationException
     * @throws ImportacionInvalida
     */
    public function __invoke(User $admin, string $contenido, bool $soloValidar = false): ResultadoImportacion
    {
        Gate::forUser($admin)->authorize('importar', User::class);

        $registros = $this->leer($contenido);
        $grupos = Grupo::query()->get()->keyBy(fn (Grupo $g) => $this->normalizar($g->nombre));
        $unidades = Unidad::query()->get()->keyBy(fn (Unidad $u) => $this->normalizar($u->nombre));
        $patrullas = Patrulla::query()->get()->keyBy(fn (Patrulla $p) => $this->clavePatrulla($p->unidad_id, $p->grupo_id, $p->nombre));

        $cisExistentes = User::query()->whereIn('ci', collect($registros)->map(fn ($r) => ReglasParticipante::normalizarCi($r['campos']['ci']))->all())->pluck('ci')->flip();

        $errores = [];
        $aCrearPatrullas = [];
        $aCrear = [];
        $vistos = [];

        foreach ($registros as ['linea' => $linea, 'campos' => $c]) {
            $fila = $this->analizarFila($linea, $c, $grupos, $unidades, $patrullas, $cisExistentes, $vistos, $aCrearPatrullas);

            array_push($errores, ...$fila['errores']);

            if ($fila['errores'] === []) {
                $aCrear[] = $fila['datos'];
            }
        }

        $patrullasNuevas = array_values(array_map(fn ($p) => "{$p['nombre']} ({$p['unidad']})", $aCrearPatrullas));

        if ($errores !== [] || $soloValidar) {
            return new ResultadoImportacion(count($registros), 0, $errores, $patrullasNuevas, $soloValidar);
        }

        DB::transaction(function () use ($aCrear, $aCrearPatrullas, &$patrullas) {
            foreach ($aCrearPatrullas as $clave => $p) {
                $patrullas[$clave] = Patrulla::create(['nombre' => $p['nombre'], 'grupo_id' => $p['grupo_id'], 'unidad_id' => $p['unidad_id']]);
            }

            foreach ($aCrear as $d) {
                User::create([
                    'ci' => $d['ci'],
                    'name' => $d['nombre'],
                    'rol' => $d['rol'],
                    'grupo_id' => $d['grupo_id'],
                    'unidad_id' => $d['unidad_id'],
                    'patrulla_id' => $d['patrulla_clave'] ? $patrullas[$d['patrulla_clave']]->getKey() : null,
                ]);
            }
        });

        activity('importacion')->causedBy($admin)
            ->withProperties(['participantes' => count($aCrear), 'patrullas_nuevas' => count($aCrearPatrullas)])
            ->log('Importó participantes desde un CSV');

        return new ResultadoImportacion(count($registros), count($aCrear), [], $patrullasNuevas, false);
    }

    /**
     * @param  array<string, string>  $c
     * @return array{errores: list<array{linea: int, campo: string, mensaje: string}>, datos: array<string, mixed>}
     */
    private function analizarFila(int $linea, array $c, $grupos, $unidades, $patrullas, $cisExistentes, array &$vistos, array &$aCrearPatrullas): array
    {
        $errores = [];
        $error = function (string $campo, string $mensaje) use (&$errores, $linea) {
            $errores[] = ['linea' => $linea, 'campo' => $campo, 'mensaje' => $mensaje];
        };

        $ci = ReglasParticipante::normalizarCi($c['ci']);
        $rolTexto = $this->normalizar($c['rol'] ?? '');
        $rol = $rolTexto === '' ? Rol::Beneficiario : (self::ROLES_CSV[$rolTexto] ?? null);

        if ($rol === null) {
            $error('rol', "Rol \"{$c['rol']}\" no válido. Usá: beneficiario, dirigente o tienda.");
        }

        [$grupo, $grupoAmbiguo] = $this->resolver($c['grupo'], $grupos);
        if ($c['grupo'] !== '' && ! $grupo) {
            $error('grupo', ($grupoAmbiguo ? "El grupo \"{$c['grupo']}\" es ambiguo" : "El grupo \"{$c['grupo']}\" no existe").'. Grupos cargados: '.$grupos->map->nombre->implode(', ').'.');
        }

        [$unidad, $unidadAmbigua] = $this->resolver($c['unidad'], $unidades);
        if ($c['unidad'] !== '' && ! $unidad) {
            $error('unidad', ($unidadAmbigua ? "La unidad \"{$c['unidad']}\" es ambigua" : "La unidad \"{$c['unidad']}\" no existe").'. Unidades cargadas: '.$unidades->map->nombre->implode(', ').'.');
        }

        $patrullaNombre = trim($c['patrulla'] ?? '');
        $claveExistente = $grupo && $unidad && $patrullaNombre !== '' ? $this->clavePatrulla($unidad->getKey(), $grupo->getKey(), $patrullaNombre) : null;
        $patrullaExiste = $claveExistente && isset($patrullas[$claveExistente]);
        $patrullaNueva = $claveExistente && ! $patrullaExiste;

        // Reglas de estructura compartidas con el formulario del panel (sin repetir mensajes de grupo/unidad).
        $reglas = ReglasParticipante::validar([
            'ci' => $ci,
            'nombre' => $c['nombre'],
            'rol' => $rol ?? Rol::Beneficiario,
            'grupo_id' => $grupo?->getKey(),
            'unidad_id' => $unidad?->getKey(),
            'patrulla_id' => $patrullaExiste ? $patrullas[$claveExistente]->getKey() : null,
            'patrulla_nueva' => $patrullaNueva || ($patrullaNombre !== '' && ! $claveExistente),
        ]);

        foreach ($reglas as $campo => $mensaje) {
            $yaDicho = ($campo === 'grupo' && $c['grupo'] !== '' && ! $grupo) || ($campo === 'unidad' && $c['unidad'] !== '' && ! $unidad);

            if (! $yaDicho) {
                $error($campo, $mensaje);
            }
        }

        if (isset($cisExistentes[$ci])) {
            $error('ci', "El CI {$ci} ya está cargado en la plataforma.");
        } elseif ($ci !== '' && isset($vistos[$ci])) {
            $error('ci', "El CI {$ci} está repetido: ya figura en la línea {$vistos[$ci]}.");
        }
        $vistos[$ci] ??= $linea;

        if ($errores === [] && $patrullaNueva) {
            $aCrearPatrullas[$claveExistente] ??= [
                'nombre' => $patrullaNombre,
                'grupo_id' => $grupo->getKey(),
                'unidad_id' => $unidad->getKey(),
                'unidad' => $unidad->nombre,
            ];
        }

        return [
            'errores' => $errores,
            'datos' => [
                'ci' => $ci,
                'nombre' => trim($c['nombre']),
                'rol' => $rol,
                'grupo_id' => $grupo?->getKey(),
                'unidad_id' => $unidad?->getKey(),
                'patrulla_clave' => $claveExistente,
            ],
        ];
    }

    /** @return list<array{linea: int, campos: array<string, string>}> */
    private function leer(string $contenido): array
    {
        if (strlen($contenido) > self::MAX_BYTES) {
            throw new ImportacionInvalida('El archivo pesa más de 2 MB. Dividilo en partes.');
        }

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252'); // Excel en Windows
        }

        if (trim($contenido) === '') {
            throw new ImportacionInvalida('El archivo está vacío.');
        }

        $primera = strtok($contenido, "\r\n");
        $delimitador = collect([';', ',', "\t"])->sortByDesc(fn ($d) => substr_count($primera, $d))->first();

        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, $contenido);
        rewind($flujo);

        $encabezado = fgetcsv($flujo, 0, $delimitador, '"', '');
        $columnas = $this->mapearColumnas($encabezado ?: []);

        $faltan = array_diff(self::OBLIGATORIAS, array_keys($columnas));
        if ($faltan !== []) {
            throw new ImportacionInvalida('Faltan columnas en el archivo: '.implode(', ', $faltan).'. Las columnas son: ci, nombre, grupo, unidad (y opcionales rol y patrulla).');
        }

        $registros = [];
        $linea = 1;

        while (($fila = fgetcsv($flujo, 0, $delimitador, '"', '')) !== false) {
            $linea++;

            if ($fila === [null] || count(array_filter($fila, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // línea en blanco
            }

            $campos = [];
            foreach (array_keys(self::ALIAS) as $campo) {
                $campos[$campo] = isset($columnas[$campo]) ? trim((string) ($fila[$columnas[$campo]] ?? '')) : '';
            }

            $registros[] = ['linea' => $linea, 'campos' => $campos];

            if (count($registros) > self::MAX_FILAS) {
                throw new ImportacionInvalida('El archivo tiene más de '.self::MAX_FILAS.' filas. Dividilo en partes.');
            }
        }

        fclose($flujo);

        if ($registros === []) {
            throw new ImportacionInvalida('El archivo no tiene participantes debajo del encabezado.');
        }

        return $registros;
    }

    /** @return array<string, int> campo → posición de la columna */
    private function mapearColumnas(array $encabezado): array
    {
        $mapa = [];

        foreach ($encabezado as $posicion => $texto) {
            $clave = preg_replace('/[^a-z0-9]/', '', $this->normalizar((string) $texto));

            foreach (self::ALIAS as $campo => $alias) {
                if (in_array($clave, $alias, true)) {
                    $mapa[$campo] ??= $posicion;
                }
            }
        }

        return $mapa;
    }

    /**
     * Busca por nombre normalizado: exacto primero y, si no, una única coincidencia parcial
     * ("General Genes" o "Genes" encuentran "G.S. General Genes"). Si hay más de una, es ambiguo.
     *
     * @return array{0: Model|null, 1: bool} [modelo encontrado, ¿ambiguo?]
     */
    private function resolver(string $texto, Collection $items): array
    {
        $buscado = $this->normalizar($texto);

        if ($buscado === '') {
            return [null, false];
        }

        if ($items->has($buscado)) {
            return [$items[$buscado], false];
        }

        if (mb_strlen($buscado) < 3) {
            return [null, false];
        }

        $parciales = $items->filter(fn ($item, $clave) => str_contains($clave, $buscado));

        return $parciales->count() === 1 ? [$parciales->first(), false] : [null, $parciales->count() > 1];
    }

    /** Minúsculas, sin tildes y con espacios simples: "  Águilas " → "aguilas". */
    private function normalizar(string $texto): string
    {
        return preg_replace('/\s+/', ' ', Str::ascii(mb_strtolower(trim($texto))));
    }

    private function clavePatrulla(int $unidadId, int $grupoId, string $nombre): string
    {
        return "{$unidadId}:{$grupoId}:".$this->normalizar($nombre);
    }
}
