<?php

namespace App\Enums;

enum UtilitySubType: string
{
    case GENERAL = 'general';
    case LUBP = 'LUBP';
    case UBP = 'UBP';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::LUBP => 'LUBP',
            self::UBP => 'UBP',
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

