<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Cómo está el servidor, visto desde la WEB (que puede tener otra configuración de PHP que la consola): límites de subida de
 * fotos, memoria, extensiones, hora y cola. Sirve para descubrir problemas del hosting antes de que los sufra un participante.
 */
class EstadoDelServidor extends Page
{
    protected string $view = 'filament.pages.estado-del-servidor';

    protected static ?string $slug = 'estado-del-servidor';

    protected static ?string $title = 'Estado del servidor';

    protected static ?string $navigationLabel = 'Estado del servidor';

    protected static string|UnitEnum|null $navigationGroup = 'Resultados';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    /** @return list<array{nombre: string, valor: string, estado: string, ayuda: string}> estado: ok | aviso | error */
    public function revisiones(): array
    {
        $mb = fn (string $v) => self::aMegabytes($v);
        $subida = $mb((string) ini_get('upload_max_filesize'));
        $post = $mb((string) ini_get('post_max_size'));
        $memoria = (string) ini_get('memory_limit');
        $pendientes = DB::table('jobs')->count();
        $fallidos = DB::table('failed_jobs')->count();
        $ahora = now();

        $fila = fn (string $nombre, string $valor, bool $ok, string $ayuda, string $malo = 'aviso') => ['nombre' => $nombre, 'valor' => $valor, 'estado' => $ok ? 'ok' : $malo, 'ayuda' => $ayuda];

        return [
            $fila('Tamaño máximo de una subida (upload_max_filesize)', (string) ini_get('upload_max_filesize'), $subida >= 8, 'La app achica las fotos en el celular, pero conviene al menos 8M para los formatos que no se pueden achicar. En Plesk: Sitios web y dominios → Configuración de PHP.'),
            $fila('Tamaño máximo de un envío (post_max_size)', (string) ini_get('post_max_size'), $post >= 16, 'Tiene que ser mayor que el anterior (recomendado 16M o más).'),
            $fila('Memoria de PHP (memory_limit)', $memoria, $memoria === '-1' || $mb($memoria) >= 128, 'Recomendado 128M o más para procesar fotos.'),
            $fila('Extensión GD (procesa las fotos)', extension_loaded('gd') ? 'activa' : 'falta', extension_loaded('gd'), 'Sin GD no se pueden procesar las fotos de las entregas.', 'error'),
            $fila('Extensión intl', extension_loaded('intl') ? 'activa' : 'falta', extension_loaded('intl'), 'Necesaria para el panel y los formatos en español.', 'error'),
            $fila('Hora del servidor', $ahora->format('d/m/Y H:i').' ('.config('app.timezone').')', config('app.timezone') === 'America/Asuncion', 'Todas las horas de misiones, talleres y turnos usan esta hora, no la del celular.', 'error'),
            $fila('Fotos esperando procesarse (cola)', (string) $pendientes, $pendientes < 50, 'Si crece y no baja, la tarea programada de la cola (queue:work) no está corriendo.'),
            $fila('Trabajos fallidos en la cola', (string) $fallidos, $fallidos === 0, 'Una foto que falló queda «procesando». Revisalo con `php artisan queue:failed`.'),
            $fila('PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='), 'Versión de PHP con la que corre el sitio.', 'error'),
        ];
    }

    private static function aMegabytes(string $valor): float
    {
        $valor = trim($valor);
        $numero = (float) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024,
            'k' => $numero / 1024,
            'm' => $numero,
            default => $numero / 1048576,
        };
    }
}
