<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Lista de control del despliegue: revisa que la configuración sea la de un servidor real y no la de una máquina de
 * desarrollo. Termina con error si algo CRÍTICO falla, para poder usarlo antes de abrir el evento y en el cron.
 */
#[AsCommand(name: 'jota:verificar-produccion', description: 'Revisa la configuración de seguridad y despliegue antes de abrir el evento')]
class VerificarProduccion extends Command
{
    protected $signature = 'jota:verificar-produccion';

    /** @var list<array{0: string, 1: bool, 2: string, 3: bool}> */
    private array $resultados = [];

    public function handle(): int
    {
        $this->revisarEntorno();
        $this->revisarSesion();
        $this->revisarArchivos();
        $this->revisarBase();

        $fallas = 0;
        $avisos = 0;

        foreach ($this->resultados as [$titulo, $ok, $detalle, $critico]) {
            if ($ok) {
                $this->line("  <info>✔</info> {$titulo}");

                continue;
            }

            $critico ? $fallas++ : $avisos++;
            $this->line('  '.($critico ? '<error> ✘ </error>' : '<comment>⚠</comment>')." {$titulo} — {$detalle}");
        }

        $this->newLine();

        if ($fallas > 0) {
            $this->error("{$fallas} problema(s) crítico(s). No abras el evento así.");

            return self::FAILURE;
        }

        $avisos > 0
            ? $this->warn("Sin problemas críticos, {$avisos} aviso(s) para revisar.")
            : $this->info('Todo en orden para producción.');

        return self::SUCCESS;
    }

    private function revisarEntorno(): void
    {
        $url = (string) config('app.url');

        $this->chequeo('APP_ENV es «production»', app()->isProduction(), 'está en «'.app()->environment().'»');
        $this->chequeo('APP_DEBUG está apagado', config('app.debug') === false, 'APP_DEBUG=true muestra datos internos ante un error');
        $this->chequeo('APP_KEY definida', filled(config('app.key')), 'falta correr php artisan key:generate');
        $this->chequeo('APP_URL usa https y no es localhost', str_starts_with($url, 'https://') && ! str_contains($url, 'localhost'), "vale «{$url}»: los QR y los enlaces firmados salen con esa dirección");
        $this->chequeo('El nivel de registro no es «debug»', config('logging.channels.'.config('logging.default').'.level', 'debug') !== 'debug', 'LOG_LEVEL=debug llena el disco y guarda de más', critico: false);
        $this->chequeo('Hay proxies de confianza configurados (TRUSTED_PROXIES)', filled(env('TRUSTED_PROXIES')), 'sin eso Laravel no sabe que la conexión original era https', critico: false);
        $this->chequeo('La cola no es «sync»', config('queue.default') !== 'sync', 'las fotos se procesarían dentro de la petición');
        $this->chequeo('La caché no es «array»', config('cache.default') !== 'array', 'se perdería en cada petición (el ranking y los límites dependen de ella)');
        $this->chequeo('La vista previa del diseño (/diseno) no está publicada', ! Route::has('diseno.guia'), 'esas pantallas con datos de ejemplo no deben existir en producción');
    }

    private function revisarSesion(): void
    {
        $this->chequeo('La cookie de sesión es solo https (SESSION_SECURE_COOKIE)', config('session.secure') === true, 'agregá SESSION_SECURE_COOKIE=true');
        $this->chequeo('La cookie de sesión no se lee desde JavaScript', config('session.http_only') === true, 'SESSION_HTTP_ONLY debe ser true');
        $this->chequeo('La cookie de sesión tiene SameSite', in_array(config('session.same_site'), ['lax', 'strict'], true), 'SESSION_SAME_SITE debe ser lax o strict');
        $this->chequeo('La sesión no usa «array» ni «cookie»', ! in_array(config('session.driver'), ['array', 'cookie'], true), 'driver «'.config('session.driver').'»');
    }

    private function revisarArchivos(): void
    {
        $this->chequeo('No existe public/hot (servidor de desarrollo de Vite)', ! file_exists(public_path('hot')), 'borrá public/hot: haría que la página busque los estilos en localhost');
        $this->chequeo('Existe el build de estilos y scripts (public/build)', file_exists(public_path('build/manifest.json')), 'falta correr npm run build');
        $this->chequeo('No hay .env dentro de public/', ! file_exists(public_path('.env')), 'se podría descargar por la web');
        $this->chequeo('El disco de fotos es privado y está fuera de public/', ! str_starts_with((string) config('filesystems.disks.private.root'), public_path()), 'las fotos de menores no pueden quedar publicadas');
        $this->chequeo('storage/ y bootstrap/cache/ se pueden escribir', is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'revisá los permisos');
    }

    private function revisarBase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->chequeo('Conexión a la base de datos', false, $e->getMessage());

            return;
        }

        $this->chequeo('Conexión a la base de datos', true);

        $pendientes = collect(app('migrator')->getMigrationFiles(database_path('migrations')))
            ->keys()->diff(DB::table('migrations')->pluck('migration'))->count();
        $this->chequeo('No hay migraciones pendientes', $pendientes === 0, "{$pendientes} sin aplicar: php artisan migrate --force");

        $conPinDePrueba = User::query()->whereNotNull('pin_hash')->whereBetween('ci', ['4000001', '4000099'])->get()
            ->filter(fn (User $u) => Hash::check((string) config('jota.seed_pin'), $u->pin_hash))->count();
        $this->chequeo('No hay cuentas con el PIN de prueba', $conPinDePrueba === 0, "{$conPinDePrueba} cuenta(s) del seeder con el PIN de prueba: borralas o cambiales el PIN");

        $admins = User::query()->where('rol', 'admin')->whereNotNull('activado_at')->count();
        $this->chequeo('Hay al menos un administrador activo', $admins > 0, 'creá uno con php artisan jota:crear-admin');

        $fallidos = DB::table('failed_jobs')->count();
        $this->chequeo('No hay trabajos fallidos en la cola', $fallidos === 0, "{$fallidos} trabajo(s) fallido(s): php artisan queue:failed", critico: false);
    }

    private function chequeo(string $titulo, bool $ok, string $detalle = '', bool $critico = true): void
    {
        $this->resultados[] = [$titulo, $ok, $detalle, $critico];
    }
}
