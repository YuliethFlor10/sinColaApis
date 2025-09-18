<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'nacimiento' => $this->faker->date('Y-m-d', '2000-01-01'),
            'genero' => $this->faker->randomElement(['M', 'F']),
            'clave' => static::$password ??= Hash::make('password'),
            'tipo_identificacion_id' => 1,  // Ajusta si tienes ids válidos
            'identificacion' => $this->faker->numerify('########'),
            'celular' => $this->faker->phoneNumber(),
            'telefono' => $this->faker->phoneNumber(),
            'direccion' => $this->faker->address(),
            'terminos_condiciones' => true,
            'estados_id' => 1,
            'roles_id' => 1,
            'negocios_id' => 1,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
