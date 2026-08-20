<?php

namespace App\Domain\Optimization\Enums;

enum TriggerType: string
{
    case Bottleneck = 'bottleneck';
    case OverAllocation = 'over_allocation';

    public function label(): string
    {
        return match ($this) {
            self::Bottleneck => 'Bottleneck',
            self::OverAllocation => 'Over-Allocation',
        };
    }
}
