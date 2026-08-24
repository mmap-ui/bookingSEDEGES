<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plate' => strtoupper(fake()->unique()->regexify('[0-9]{4}[A-Z]{3}')),
            'brand' => fake()->randomElement(['Toyota', 'Nissan', 'Kia', 'Hyundai', 'Ford', 'Chevrolet']),
            'model' => fake()->randomElement(['Corolla', 'Sentra', 'Picanto', 'Accent', 'Fiesta', 'Sail']),
            'year' => fake()->numberBetween(2015, 2024),
            'capacity' => fake()->numberBetween(4, 12),
            'status' => VehicleStatus::Disponible,
            'driver_id' => null,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Mantenimiento,
        ]);
    }

    public function assignedTo(?User $driver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => $driver?->id ?? User::factory(),
        ]);
    }
}
