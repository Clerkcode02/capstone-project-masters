<?php

namespace App\Domain\Capacity\Enums;

enum PerformanceTier: string
{
    case Below = 'below';
    case Acceptable = 'acceptable';
    case Over = 'over';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Below => 'Below Target',
            self::Acceptable => 'Acceptable',
            self::Over => 'Over Target',
            self::NotApplicable => 'Not Applicable',
        };
    }
}
