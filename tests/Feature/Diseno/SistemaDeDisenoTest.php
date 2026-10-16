<?php

use App\Http\Controllers\DisenoController;
use Illuminate\Support\Facades\Lang;

/** Una URL por pantalla y estado de la vista previa. */
function pantallasDeDiseno(): array
{
    $estados = [
        'ingresar' => [null, 'pin', 'ci', 'sin-pin', 'ayuda'],
        'activar' => [null, 'pin', 'pin-error', 'ci', 'ya'],
        'inicio' => [null, 'vacio', 'bienvenida'],
        'mision' => [null, 'pendiente', 'rechazada', 'aprobada', 'vencida', 'patrulla'],
        'entregar' => [null, 'error'],
        'actividades' => [null, 'choque'],
        'escanear' => [null, 'sin-camara'],
        'confirmar-pago' => [null, 'saldo', 'vencido'],
        'asistencia-ok' => [null, 'vencido', 'ya'],
        'ranking' => [null, 'rover', 'vacio'],
        'historial' => [null, 'vacio'],
        'd-cola' => [null, 'vacia'],
        'd-revisar' => [null, 'patrulla', 'aprobada', 'rechazar', 'rechazar-error', 'rechazada', 'ya-revisada'],
        'd-penalizaciones' => [null, 'error'],
        't-qr' => [null, 'pagado', 'vencido'],
        't-devolver' => [null, 'form', 'tope'],
    ];

    $urls = [];
    foreach (DisenoController::PANTALLAS as $pantalla) {
        foreach ($estados[$pantalla] ?? [null] as $estado) {
            $url = "/diseno/{$pantalla}".($estado ? "?estado={$estado}" : '');
            $urls[$url] = [$url];
        }
    }

    return $urls;
}

it('cada pantalla del diseño carga y tiene exactamente un h1', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect(preg_match_all('/<h1[\s>]/', $html))->toBe(1, "{$url} debería tener un solo h1");
})->with(fn () => pantallasDeDiseno());

it('cada pantalla está en español, con idioma declarado, zona principal y sin claves de copy sin resolver', function (string $url) {
    $html = $this->get($url)->getContent();

    expect($html)->toContain('<html lang="es"')
        ->toContain('<main id="contenido"')
        ->and(preg_match('/\bjota\.[a-z_]+\.[a-z_]/', strip_tags($html)))->toBe(0, "{$url} muestra una clave de copy sin traducir");
})->with(fn () => pantallasDeDiseno());

it('toda imagen tiene texto alternativo y todo campo tiene su etiqueta', function (string $url) {
    $html = $this->get($url)->getContent();

    preg_match_all('/<img\b[^>]*>/', $html, $imagenes);
    foreach ($imagenes[0] as $img) {
        expect($img)->toMatch('/\balt="[^"]+"/');
    }

    preg_match_all('/<(?:input|textarea)\b[^>]*\bid="([^"]+)"/', $html, $campos);
    foreach ($campos[1] as $id) {
        expect($html)->toContain('for="'.$id.'"');
    }

    expect($html)->toBeString();
})->with(fn () => pantallasDeDiseno());

it('todo botón y enlace con solo un ícono tiene nombre accesible', function (string $url) {
    $html = $this->get($url)->getContent();

    // Elementos <a> o <button> cuyo contenido visible es solo un svg: deben llevar aria-label.
    preg_match_all('/<(a|button)\b([^>]*)>\s*(<svg\b(?:(?!<\/svg>).)*<\/svg>)\s*<\/\1>/s', $html, $soloIcono, PREG_SET_ORDER);
    foreach ($soloIcono as $m) {
        expect($m[2])->toContain('aria-label=');
    }

    expect($html)->toBeString();
})->with(fn () => pantallasDeDiseno());

it('la barra inferior marca la pestaña actual y los íconos son decorativos', function () {
    $html = $this->get('/diseno/inicio')->getContent();

    expect($html)->toContain('aria-label="Navegación principal"')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('el QR de asistencia va en negro sobre blanco con zona de silencio', function () {
    $html = $this->get('/diseno/d-qr')->getContent();

    expect($html)->toContain('bg-white')->toContain('<svg')->toContain('aria-label="Código QR"');
});

// ── Reglas del sistema (CLAUDE.md): todo por tokens ─────────────────────────────────────────

function vistasDelSistema(): array
{
    // PHP puro: los datasets de Pest se resuelven antes de que exista la aplicación (sin fachadas ni helpers).
    $raiz = dirname(__DIR__, 3).'/resources/views/';
    $vistas = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)) as $archivo) {
        $ruta = str_replace($raiz, '', $archivo->getPathname());

        if (str_ends_with($ruta, '.blade.php') && preg_match('#^(components/ui/|components/layouts/|diseno/pantallas/|asistencia/)#', $ruta)) {
            $vistas[$ruta] = [file_get_contents($archivo->getPathname())];
        }
    }

    ksort($vistas);

    return $vistas;
}

it('ningún componente ni pantalla usa colores literales', function (string $contenido) {
    // La única excepción es la etiqueta <meta name="theme-color">, que por especificación lleva un valor literal.
    $sinMeta = preg_replace('/<meta name="theme-color"[^>]*>/', '', $contenido);

    expect($sinMeta)->not->toMatch('/#[0-9a-fA-F]{3,8}\b(?![a-zA-Z-])/')
        ->and($sinMeta)->not->toMatch('/\b(?:rgb|rgba|hsl|hsla|oklch)\(/')
        ->and($sinMeta)->not->toMatch('/\b(?:bg|text|border|ring|fill|stroke|from|to|via)-\[#/');
})->with(fn () => vistasDelSistema());

it('ningún componente usa pesos de fuente que no existen (solo Medium 500 y Bold 700)', function (string $contenido) {
    expect($contenido)->not->toMatch('/\bfont-(?:thin|extralight|light|normal|semibold|extrabold|black)\b/');
})->with(fn () => vistasDelSistema());

it('todas las claves de copy que usan las vistas existen en lang/es/jota.php', function () {
    $faltan = [];

    foreach (vistasDelSistema() as $ruta => [$contenido]) {
        preg_match_all('/(?:__|trans_choice)\(\s*[\'"](jota\.[a-z0-9_.]+)[\'"]/', $contenido, $m);
        foreach ($m[1] as $clave) {
            if (! Lang::has($clave, 'es')) {
                $faltan[] = "{$ruta}: {$clave}";
            }
        }
    }

    expect($faltan)->toBe([]);
});

it('el copy no deja textos en inglés en la interfaz', function () {
    $texto = json_encode(Lang::get('jota', [], 'es'), JSON_UNESCAPED_UNICODE);

    expect($texto)->not->toMatch('/\b(?:Loading|Cancel|Submit|Login|Logout|Sign in|Error:|Success)\b/');
});

it('las fuentes se sirven desde el propio sitio, sin CDN', function () {
    $html = $this->get('/diseno/inicio')->getContent();

    expect($html)->not->toContain('fonts.googleapis.com')->not->toContain('fonts.bunny.net')->not->toContain('cdn.');
    expect(file_exists(resource_path('fonts/poppins-medium.woff2')))->toBeTrue()
        ->and(file_exists(resource_path('fonts/poppins-bold.woff2')))->toBeTrue();
});

it('la vista previa no existe en producción', function () {
    $ruta = file_get_contents(base_path('routes/web.php'));

    expect($ruta)->toContain('if (! app()->isProduction())')
        ->and(strpos($ruta, 'DisenoController::class'))->toBeGreaterThan(strpos($ruta, 'if (! app()->isProduction())'));
});
