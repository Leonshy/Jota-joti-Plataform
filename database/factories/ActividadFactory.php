<?php

namespace Database\Factories;

use App\Enums\Modalidad;
use App\Enums\TipoActividad;
use App\Models\Actividad;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Actividad> */
class ActividadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo' => TipoActividad::Taller,
            'titulo' => fake()->sentence(3),
            'lugar' => 'Salón grande',
            'modalidad' => Modalidad::Individual,
            'puntos' => 20,
        ];
    }

    public function porEquipo(): static
    {
        return $this->state(fn () => ['modalidad' => Modalidad::Patrulla, 'tipo' => TipoActividad::EstacionJota]);
    }
}
