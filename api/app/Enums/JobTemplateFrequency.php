<?php

namespace App\Enums;

enum JobTemplateFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case ON_DEMAND = 'on_demand';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Harian',
            self::WEEKLY => 'Mingguan',
            self::MONTHLY => 'Bulanan',
            self::QUARTERLY => 'Triwulanan',
            self::YEARLY => 'Tahunan',
            self::ON_DEMAND => 'Sesuai Kebutuhan',
        };
    }

    public static function getLabels(): array
    {
        return [
            self::DAILY->value => self::DAILY->getLabel(),
            self::WEEKLY->value => self::WEEKLY->getLabel(),
            self::MONTHLY->value => self::MONTHLY->getLabel(),
            self::QUARTERLY->value => self::QUARTERLY->getLabel(),
            self::YEARLY->value => self::YEARLY->getLabel(),
            self::ON_DEMAND->value => self::ON_DEMAND->getLabel(),
        ];
    }
}
