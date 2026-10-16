<?php

namespace Database\Factories;

use App\Models\Grupo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grupo> */
class GrupoFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => 'G.S. '.fake()->unique()->lastName()];
    }
}
