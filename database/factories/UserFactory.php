<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $pin;

    public function definition(): array
    {
        return [
            'ci' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'name' => fake()->name(),
            'rol' => Rol::Beneficiario,
            'pin_hash' => static::$pin ??= Hash::make('1234'),
            'activado_at' => now(),
        ];
    }

    public function sinActivar(): static
    {
        return $this->state(fn () => ['pin_hash' => null, 'activado_at' => null]);
    }

    public function rol(Rol $rol): static
    {
        return $this->state(fn () => ['rol' => $rol]);
    }
}
