<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Seed a few sample vehicles for demonstration purposes.
     */
    public function run(): void
    {
        $vehicles = [
            ['plate' => '2370TLF', 'brand' => 'Toyota', 'model' => 'Corolla', 'year' => 2022, 'capacity' => 5, 'status' => 'disponible'],
            ['plate' => '9845BNM', 'brand' => 'Nissan', 'model' => 'Sentra', 'year' => 2021, 'capacity' => 5, 'status' => 'disponible'],
            ['plate' => '1234ABC', 'brand' => 'Hyundai', 'model' => 'H1', 'year' => 2020, 'capacity' => 10, 'status' => 'mantenimiento'],
            ['plate' => '5678DEF', 'brand' => 'Kia', 'model' => 'Picanto', 'year' => 2023, 'capacity' => 4, 'status' => 'disponible'],
        ];

        foreach ($vehicles as $data) {
            Vehicle::firstOrCreate(['plate' => $data['plate']], $data);
        }
    }
}
