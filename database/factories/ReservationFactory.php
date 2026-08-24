<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', '+2 weeks');

        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+3 hours'),
            'motive' => fake()->sentence(4),
            'destination' => fake()->city(),
            'status' => ReservationStatus::Pendiente,
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Aprobada,
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Rechazada,
            'rejection_reason' => $reason ?? fake()->sentence(3),
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelada,
        ]);
    }
}
