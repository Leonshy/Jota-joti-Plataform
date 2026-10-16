<?php

namespace Database\Factories;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Mision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Entrega> */
class EntregaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mision_id' => Mision::factory(),
            'user_id' => User::factory(),
            'texto' => fake()->sentence(),
            'estado' => EstadoEntrega::Pendiente,
        ];
    }
}
