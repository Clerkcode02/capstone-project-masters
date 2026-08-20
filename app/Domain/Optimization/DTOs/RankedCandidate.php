<?php

namespace App\Domain\Optimization\DTOs;

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Models\User;

final readonly class RankedCandidate
{
    public function __construct(
        public User $user,
        public int $workloadScore,
        public ?float $effectiveAvailabilityHours,
        public ?PerformanceTier $performanceTier,
    ) {}
}
