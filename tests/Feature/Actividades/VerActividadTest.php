<?php

use App\Models\Reserva;
use Illuminate\Support\Facades\Artisan;

/** Corre el comando y devuelve todo lo que imprimió. */
function verActividad(array $args): string
{
    Artisan::call('jota:ver-actividad', $args);

    return Artisan::output();
}

beforeEach(function () {
    $this->e = escenario(8);
    $this->yo = $this->e->miembros[0];
    $this->taller = actividadPara($this->e->unidad, ['titulo' => 'IA para tus ideas', 'lugar' => 'Aula 2']);
});

it('muestra la configuración del taller, sus turnos, cupos y reservas', function () {
    $turno = turnoDe($this->taller, '+2 hours', '+3 hours', cupo: 50);
    foreach ($this->e->miembros->slice(1, 6) as $m) {
        Reserva::create(['turno_id' => $turno->id, 'user_id' => $m->id]);
    }

    expect(verActividad(['texto' => 'IA para']))->toContain('IA para tus ideas', 'modalidad: individual', 'lugar: Aula 2', 'abierto')
        ->toMatch('/\|\s*50\s*\|\s*6\s*\|\s*44\s*\|\s*abierto/');
});

it('con un CI prueba la reserva y dice que sí se puede, sin dejar ninguna reserva guardada', function () {
    turnoDe($this->taller, '+2 hours', '+3 hours', cupo: 50);

    expect(verActividad(['texto' => 'IA', '--ci' => $this->yo->ci]))->toContain('SÍ se puede');

    expect(Reserva::count())->toBe(0);
});

it('explica por qué NO: turno lleno, ya empezó, choque de horario y ya apuntado a ese taller', function () {
    $lleno = turnoDe($this->taller, '+2 hours', '+3 hours', cupo: 1);
    Reserva::create(['turno_id' => $lleno->id, 'user_id' => $this->e->miembros[1]->id]);
    $empezado = turnoDe($this->taller, '-1 hour', '+1 hour', cupo: 50);

    expect(verActividad(['texto' => 'IA', '--ci' => $this->yo->ci]))->toContain('TurnoLleno', 'YA EMPEZÓ', 'TurnoYaComenzo');

    // Choque con otra reserva suya a la misma hora.
    $otro = actividadPara($this->e->unidad, ['titulo' => 'Ciberseguridad']);
    $mismaHora = turnoDe($otro, '+5 hours', '+6 hours', cupo: 10);
    Reserva::create(['turno_id' => $mismaHora->id, 'user_id' => $this->yo->id]);
    turnoDe($this->taller, '+5 hours', '+6 hours', cupo: 50);

    expect(verActividad(['texto' => 'IA', '--ci' => $this->yo->ci]))->toContain('NO: choca con «Ciberseguridad»');

    expect(Reserva::count())->toBe(2); // las que ya había: la prueba no sumó ninguna
});

it('avisa si no hay actividad o si el CI no existe', function () {
    $this->artisan('jota:ver-actividad', ['texto' => 'zzz'])->expectsOutputToContain('No hay actividades')->assertFailed();
    $this->artisan('jota:ver-actividad', ['texto' => 'IA', '--ci' => '99999999'])->expectsOutputToContain('No existe una persona')->assertFailed();
});

it('el script de la clave limitada acepta «actividad» con una palabra y un CI, y rechaza lo demás', function () {
    $correr = fn (string $pedido) => trim((string) shell_exec('SSH_ORIGINAL_COMMAND='.escapeshellarg($pedido).' JOTA_PHP=true JOTA_APP=/tmp bash '.escapeshellarg(base_path('deploy/ssh-comandos.sh')).' 2>&1'));

    expect($correr('actividad IA'))->not->toContain('Comando no permitido')
        ->and($correr('actividad IA 6935094'))->not->toContain('Comando no permitido')
        ->and($correr('actividad IA para'))->toContain('Comando no permitido')           // dos palabras sin ser CI
        ->and($correr('actividad IA 6935094 extra'))->toContain('demasiados argumentos')
        ->and($correr('actividad "IA; rm -rf /"'))->toContain('Comando no permitido')
        ->and($correr('consultar 6935094 extra'))->toContain('demasiados argumentos');
});
