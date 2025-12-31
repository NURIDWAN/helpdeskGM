<?php

namespace App\Enums;

use ArchTech\Enums\Values;
use ArchTech\Enums\InvokableCases;

enum WorkReportStatus: string
{
    use Values, InvokableCases;

    case PROGRESS = 'progress';
    case FAILED = 'failed';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PROGRESS => 'In Progress',
            self::FAILED => 'Failed',
            self::COMPLETED => 'Completed',
        };
    }
}
