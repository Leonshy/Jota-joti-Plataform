<?php

use App\Enums\Rol;
use App\Livewire\Beneficiario\Entregar;
use App\Livewire\Dirigente\Revisar;
use App\Models\Entrega;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/** Rutas protegidas por sección, con parámetros inventados: el rol se revisa antes de buscar nada. */
function rutasDe(string $prefijo): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true) && str_starts_with($r->uri(), $prefijo))
        ->map(fn ($r) => preg_replace('/\{[^}]+\}/', '987654', $r->uri()))
        ->unique()->values()->all();
}

it('sin sesión, toda pantalla protegida manda a ingresar', function () {
    foreach (array_merge(rutasDe('app'), rutasDe('dirigente'), rutasDe('tienda')) as $uri) {
        $this->get('/'.$uri)->assertRedirect(route('ingresar'));
    }

    $this->get('/admin')->assertRedirect();
});

it('cada rol solo entra a lo suyo y los demás reciben 403, existan o no los registros', function (Rol $rol, array $permitidas) {
    $user = User::factory()->rol($rol)->create(['unidad_id' => Unidad::factory()->create()->id]);
    $this->actingAs($user);

    foreach (['app', 'dirigente', 'tienda'] as $seccion) {
        foreach (rutasDe($seccion) as $uri) {
            $r = $this->get('/'.$uri);

            if (in_array($seccion, $permitidas, true)) {
                // Entra: puede ser 200, o 404 por un id inventado, pero nunca 403.
                expect($r->status())->not->toBe(403, "{$rol->value} debería entrar a /{$uri}");
            } else {
                // No corresponde: 403 SIEMPRE, también con ids que no existen (si no, se podría averiguar qué ids existen).
                expect($r->status())->toBe(403, "{$rol->value} no debería entrar a /{$uri}");
            }
        }
    }
})->with([
    'participante' => [Rol::Beneficiario, ['app']],
    'dirigente' => [Rol::Dirigente, ['dirigente']],
    'tienda' => [Rol::Tienda, ['tienda']],
    'admin' => [Rol::Admin, ['dirigente', 'tienda']],
]);

it('el panel de administración es solo del administrador', function () {
    foreach ([Rol::Beneficiario, Rol::Dirigente, Rol::Tienda] as $rol) {
        $this->actingAs(User::factory()->rol($rol)->create());

        foreach (['/admin', '/admin/participantes', '/admin/misiones', '/admin/actividades', '/admin/reservas', '/admin/rankings', '/admin/importar-participantes'] as $uri) {
            expect($this->get($uri)->status())->toBeIn([403, 302], "{$rol->value} en {$uri}");
            expect($this->get($uri)->status())->not->toBe(200);
        }
    }

    $this->actingAs(User::factory()->rol(Rol::Admin)->create())->get('/admin/participantes')->assertOk();
});

it('las páginas de otra persona no se pueden abrir cambiando el número de la dirección', function () {
    $a = escenario(2);
    $b = escenario(2);
    $entregaDeB = Entrega::factory()->create(['mision_id' => misionPara($b->unidad)->id, 'user_id' => $b->miembros[0]->id]);

    // Un participante, aunque adivine el id, no ve ni revisa entregas.
    $this->actingAs($a->miembros[0])->get(route('dirigente.revisar', $entregaDeB))->assertForbidden();
    // Un dirigente de otra unidad tampoco.
    $this->actingAs($a->dirigente)->get(route('dirigente.revisar', $entregaDeB))->assertForbidden();
    // Ni la misión ajena en el celular del participante.
    $this->actingAs($a->miembros[0])->get(route('app.mision', $entregaDeB->mision))->assertForbidden();
});

it('las propiedades bloqueadas de los componentes no se pueden cambiar desde el navegador', function () {
    $e = escenario(1);
    $propia = misionPara($e->unidad);
    $ajena = misionPara(escenario(1)->unidad);

    $c = Livewire::actingAs($e->miembros[0])->test(Entregar::class, ['mision' => $propia]);

    expect(fn () => $c->set('misionId', $ajena->id))->toThrow(CannotUpdateLockedPropertyException::class);
});

it('la tienda y el participante no entran a la validación de otras unidades por Livewire', function () {
    $e = escenario(1);
    $entrega = Entrega::factory()->create(['mision_id' => misionPara($e->unidad)->id, 'user_id' => $e->miembros[0]->id]);

    Livewire::actingAs(usuarioTienda())->test(Revisar::class, ['entrega' => $entrega])->assertForbidden();
});
