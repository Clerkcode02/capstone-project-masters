<?php

namespace App\Domain\Capacity\Enums;

enum PeriodType: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
        };
    }
}
