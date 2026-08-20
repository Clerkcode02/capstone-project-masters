<?php

namespace App\Domain\Optimization\Enums;

enum BottleneckBasis: string
{
    case AccountHistory = 'account_history';
    case TierHistory = 'tier_history';
    case StandardHours = 'standard_hours';

    public function label(): string
    {
        return match ($this) {
            self::AccountHistory => 'Account History',
            self::TierHistory => 'Tier History',
            self::StandardHours => 'Standard Hours (cold start)',
        };
    }
}
