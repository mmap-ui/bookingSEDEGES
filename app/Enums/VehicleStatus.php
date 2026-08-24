<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Disponible = 'disponible';
    case Mantenimiento = 'mantenimiento';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Mantenimiento => 'En mantenimiento',
        };
    }

    public function isMaintenance(): bool
    {
        return $this === self::Mantenimiento;
    }
}
