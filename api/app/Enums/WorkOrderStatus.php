<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';

    /**
     * Get all enum values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
        };
    }

    /**
     * Get color class for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::IN_PROGRESS => 'blue',
            self::DONE => 'green',
        };
    }
}

