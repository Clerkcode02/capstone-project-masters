<?php

namespace App\Domain\Tasks\DTOs;

use App\Models\User;

final readonly class OverAllocationCheckResult
{
    /**
     * @param  array<int, AlternativeCandidate>  $alternatives
     */
    public function __construct(
        public User $candidate,
        public int $currentScore,
        public int $prospectiveScore,
        public int $threshold,
        public bool $exceedsThreshold,
        public float $percentOverThreshold,
        public array $alternatives,
    ) {}
}
