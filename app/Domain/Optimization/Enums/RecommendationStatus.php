<?php

namespace App\Domain\Optimization\Enums;

enum RecommendationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Dismissed => 'Dismissed',
        };
    }
}
