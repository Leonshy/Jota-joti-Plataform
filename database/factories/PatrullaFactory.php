<?php

namespace Database\Factories;

use App\Models\Grupo;
use App\Models\Patrulla;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patrulla> */
class PatrullaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'grupo_id' => Grupo::factory(),
            'unidad_id' => Unidad::factory(),
        ];
    }
}
