<?php

use App\Domain\Actividades\CumplimientoYaAcreditado;
use App\Domain\Actividades\MarcarCumplimiento;
use App\Domain\Actividades\ModalidadIncorrecta;
use App\Domain\Actividades\PatrullaFueraDeActividad;
use App\Domain\Ledger\Reconciliador;
use App\Enums\Rol;
use App\Models\CumplimientoEquipo;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->e = escenario(3);
    $this->actividad = actividadPara($this->e->unidad, ['puntos' => 50], true);
    $this->marcar = app(MarcarCumplimiento::class);
});

it('cumplida: +P a la patrulla una vez y ⌈P/n⌉ LisCoins a cada integrante (50/3 = 17)', function () {
    $registro = ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);

    expect($registro->cumplida)->toBeTrue()
        ->and($registro->marcado_por)->toBe($this->e->dirigente->id)
        ->and($this->e->patrulla->fresh()->puntos)->toBe(50)
        ->and(MovPuntoPatrulla::count())->toBe(1)
        ->and(MovPuntoPatrulla::first()->registrado_por)->toBe($this->e->dirigente->id)
        ->and(MovPuntoIndividual::count())->toBe(0);

    foreach ($this->e->miembros as $m) {
        expect($m->fresh()->jlis)->toBe(17)->and($m->fresh()->puntos)->toBe(0);
    }
});

it('no cumplida: queda registrada y no acredita nada', function () {
    $registro = ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, false);

    expect($registro->cumplida)->toBeFalse()
        ->and($this->e->patrulla->fresh()->puntos)->toBe(0)
        ->and(MovJli::count())->toBe(0);
});

it('una patrulla marcada como no cumplida se puede corregir a cumplida y se acredita una vez', function () {
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, false);
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);

    expect(CumplimientoEquipo::count())->toBe(1)
        ->and(CumplimientoEquipo::first()->cumplida)->toBeTrue()
        ->and($this->e->patrulla->fresh()->puntos)->toBe(50);
});

it('marcar no cumplida dos veces es inofensivo', function () {
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, false);
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, false);

    expect(CumplimientoEquipo::count())->toBe(1);
});

it('marcar cumplida dos veces responde "ya acreditado" y no suma de nuevo', function () {
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);

    expect(fn () => ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true))
        ->toThrow(CumplimientoYaAcreditado::class)
        ->and($this->e->patrulla->fresh()->puntos)->toBe(50)
        ->and(MovPuntoPatrulla::count())->toBe(1)
        ->and(MovJli::count())->toBe(3);
});

it('una patrulla ya cumplida no se revierte desde acá', function () {
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);

    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, false);
})->throws(CumplimientoYaAcreditado::class);

it('cada patrulla se marca por separado', function () {
    $otra = Patrulla::factory()->create(['unidad_id' => $this->e->unidad->id]);
    User::factory()->count(2)->create(['unidad_id' => $this->e->unidad->id, 'patrulla_id' => $otra->id]);

    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);
    ($this->marcar)($this->e->dirigente, $this->actividad, $otra, true);

    expect($otra->fresh()->puntos)->toBe(50)
        ->and(User::where('patrulla_id', $otra->id)->first()->jlis)->toBe(25); // 50/2
});

it('el dirigente de otra unidad recibe 403 y no se acredita nada', function () {
    expect(fn () => ($this->marcar)(escenario()->dirigente, $this->actividad, $this->e->patrulla, true))
        ->toThrow(AuthorizationException::class)
        ->and(CumplimientoEquipo::count())->toBe(0)
        ->and(MovPuntoPatrulla::count())->toBe(0);
});

it('un beneficiario no marca cumplimientos', function () {
    ($this->marcar)($this->e->miembros[0], $this->actividad, $this->e->patrulla, true);
})->throws(AuthorizationException::class);

it('el admin marca en cualquier unidad', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    ($this->marcar)($admin, $this->actividad, $this->e->patrulla, true);

    expect($this->e->patrulla->fresh()->puntos)->toBe(50);
});

it('una actividad individual no tiene cumplimientos', function () {
    $individual = actividadPara($this->e->unidad);

    ($this->marcar)($this->e->dirigente, $individual, $this->e->patrulla, true);
})->throws(ModalidadIncorrecta::class);

it('no se marca una patrulla de una unidad a la que la actividad no apunta', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();
    $otraUnidad = Unidad::factory()->create();
    $ajena = Patrulla::factory()->create(['unidad_id' => $otraUnidad->id]);

    ($this->marcar)($admin, $this->actividad, $ajena, true);
})->throws(PatrullaFueraDeActividad::class);

it('una actividad de 0 puntos deja la marca sin movimientos (D4)', function () {
    $gratis = actividadPara($this->e->unidad, ['puntos' => 0], true);

    ($this->marcar)($this->e->dirigente, $gratis, $this->e->patrulla, true);

    expect(CumplimientoEquipo::count())->toBe(1)->and(MovPuntoPatrulla::count())->toBe(0)->and(MovJli::count())->toBe(0);
});

it('la reconciliación queda limpia después de un cumplimiento', function () {
    ($this->marcar)($this->e->dirigente, $this->actividad, $this->e->patrulla, true);

    expect(app(Reconciliador::class)->diferencias())->toBe([]);
});
