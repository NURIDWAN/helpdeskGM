<?php

namespace App\Enums;

enum JobTemplateFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Harian',
            self::WEEKLY => 'Mingguan',
            self::MONTHLY => 'Bulanan',
        };
    }

    public static function getLabels(): array
    {
        return [
            self::DAILY->value => self::DAILY->getLabel(),
            self::WEEKLY->value => self::WEEKLY->getLabel(),
            self::MONTHLY->value => self::MONTHLY->getLabel(),
        ];
    }
}
