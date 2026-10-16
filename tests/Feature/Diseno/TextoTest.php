<?php

use App\Support\Texto;

it('concuerda las cantidades en singular y plural', function (int $n, string $unidad, string $esperado) {
    expect(cantidad($n, $unidad))->toBe($esperado);
})->with([
    [1, 'punto', '1 punto'],
    [0, 'punto', '0 puntos'],
    [10, 'punto', '10 puntos'],
    [-5, 'punto', '-5 puntos'],
    [-1, 'punto', '-1 punto'],
    [1, 'jli', '1 LisCoin'],
    [2, 'jli', '2 LisCoins'],
    [1500, 'jli', '1.500 LisCoins'],
    [1, 'asistencia', '1 asistencia'],
    [14, 'asistencia', '14 asistencias'],
]);

it('el copy con cantidades nunca dice "1 puntos" ni "1 asistencias"', function () {
    expect(trans_choice('jota.dirigente.sesion_cerrada_texto', 1, ['n' => 1]))->toBe('Se registró 1 asistencia.')
        ->and(trans_choice('jota.dirigente.sesion_cerrada_texto', 14, ['n' => 14]))->toBe('Se registraron 14 asistencias.')
        ->and((string) copia('jota.misiones.aprobada_aviso', ['puntos' => cantidad(1, 'punto'), 'jlis' => cantidad(1, 'jli')]))
        ->toContain('1 punto</strong>')->toContain('1 LisCoin</strong>');
});

it('el énfasis violeta lo decide el copy, nunca un dato', function () {
    $html = (string) copia('jota.escanear.asistencia_ok_texto', ['actividad' => '<script>alert(1)</script> **negrita**']);

    expect($html)->toContain('<strong class="font-bold text-titulo">')
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;')
        // el "**negrita**" que vino como dato no se convierte en <strong>
        ->and(substr_count($html, '<strong'))->toBe(1);
});

it('los textos con énfasis y marcadores conviven bien: nada de asteriscos sueltos en pantalla', function () {
    foreach (['pago.ok_texto', 'misiones.aprobada_aviso', 'dirigente.acredita_individual', 'dirigente.acredita_patrulla', 'tienda.devuelto_texto'] as $clave) {
        $texto = (string) Texto::copia('jota.'.$clave, ['puntos' => '10 puntos', 'jlis' => '10 LisCoins', 'monto' => '30 LisCoins', 'tienda' => 'T', 'nombre' => 'Ana', 'saldo' => '1 LisCoin']);
        expect($texto)->not->toContain('**')->and($texto)->not->toContain(':puntos')->and($texto)->not->toContain(':jlis');
    }
});

it('el copy no deja unidades con plural fijo pegadas a un número', function () {
    $fuente = file_get_contents(dirname(__DIR__, 3).'/lang/es/jota.php');

    // «:n puntos», «:puntos puntos», «:monto LisCoins»: la unidad se pega con cantidad(), que concuerda.
    expect($fuente)->not->toMatch('/:(?:n|puntos|jlis|monto|max|faltan|saldo)\s+(?:puntos|LisCoins)\b/');
});
