<?php

namespace App\Domain\TimeTracking\Enums;

enum HourType: string
{
    case Production = 'production';
    case NonProduction = 'non_production';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Production',
            self::NonProduction => 'Non-Production',
            self::Leave => 'Leave',
        };
    }
}
