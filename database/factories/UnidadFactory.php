<?php

namespace Database\Factories;

use App\Enums\ModoRanking;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unidad> */
class UnidadFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => fake()->unique()->word(), 'modo_ranking' => ModoRanking::Equipo];
    }

    public function individual(): static
    {
        return $this->state(fn () => ['modo_ranking' => ModoRanking::Individual]);
    }
}
