<?php

use App\Models\User;

it('los errores se muestran en español y con el estilo de la app', function () {
    $this->get('/no-existe')->assertNotFound()->assertSee(__('jota.error.404.titulo'));

    $this->actingAs(User::factory()->create())->get(route('dirigente.inicio'))
        ->assertForbidden()->assertSee(__('jota.error.403.titulo'))->assertDontSee('UNAUTHORIZED');
});
