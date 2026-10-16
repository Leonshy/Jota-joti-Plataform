<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Turno> */
class TurnoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actividad_id' => Actividad::factory(),
            'inicio' => now()->addHours(2),
            'fin' => now()->addHours(3),
            'cupo' => null,
        ];
    }

    public function entre(string $inicio, string $fin): static
    {
        return $this->state(fn () => ['inicio' => now()->modify($inicio), 'fin' => now()->modify($fin)]);
    }
}
