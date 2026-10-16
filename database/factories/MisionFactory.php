<?php

namespace Database\Factories;

use App\Enums\Modalidad;
use App\Models\Mision;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mision> */
class MisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(3),
            'lugar' => 'Plaza central',
            'descripcion' => fake()->paragraph(),
            'modalidad' => Modalidad::Individual,
            'puntos' => 50,
            'lanza_at' => now()->subHour(),
            'vence_at' => null,
            'cerrada_at' => null,
        ];
    }

    public function porPatrulla(): static
    {
        return $this->state(fn () => ['modalidad' => Modalidad::Patrulla]);
    }

    public function futura(): static
    {
        return $this->state(fn () => ['lanza_at' => now()->addHour()]);
    }

    public function vencida(): static
    {
        return $this->state(fn () => ['vence_at' => now()->subMinute()]);
    }

    public function cerrada(): static
    {
        return $this->state(fn () => ['cerrada_at' => now()->subMinute()]);
    }
}
