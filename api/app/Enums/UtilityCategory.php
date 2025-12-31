<?php

namespace App\Enums;

enum UtilityCategory: string
{
    case GAS = 'gas';
    case WATER = 'water';
    case ELECTRICITY = 'electricity';

    public function label(): string
    {
        return match ($this) {
            self::GAS => 'Gas',
            self::WATER => 'Water',
            self::ELECTRICITY => 'Electricity',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

