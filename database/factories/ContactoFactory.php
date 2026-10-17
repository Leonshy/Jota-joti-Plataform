<?php

namespace Database\Factories;

use App\Enums\EstadoEntrega;
use App\Enums\TipoContacto;
use App\Models\Contacto;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contacto> */
class ContactoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo' => TipoContacto::Joti,
            'user_id' => User::factory(),
            'unidad_id' => Unidad::factory(),
            'es_local' => true,
            'estado' => EstadoEntrega::Pendiente,
            'grupo_scout' => 'Grupo Scout Ñandejara',
            'ciudad' => 'Asunción',
            'unidad_scout' => 'Tropa',
            'pais' => 'Paraguay',
        ];
    }

    public function jota(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoContacto::Jota,
            'grupo_scout' => null, 'ciudad' => null, 'unidad_scout' => null, 'pais' => null,
            'contactado_at' => now()->subMinutes(10),
            'estacion' => 'ZP5ZDM', 'rst_tx' => '5-9', 'rst_rx' => '5-9', 'frecuencia' => '7.125', 'emision' => 'LSB',
            'qsl_r' => false, 'qsl_e' => true,
        ]);
    }
}
