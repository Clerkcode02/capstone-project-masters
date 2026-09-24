<?php

namespace App\Domain\Tasks\DTOs;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Result of the pre-assignment over-allocation check (capstone Ch. 3, M3).
 * Carries the real numbers so the warning is self-explaining — never a bare
 * "over threshold" flag.
 */
class WorkloadEvaluation
{
    /**
     * @param  Collection<int, array{user: User, score: int}>  $alternatives  up to 3, ordered by (threshold − score) desc
     */
    public function __construct(
        public readonly int $currentScore,
        public readonly int $prospectiveScore,
        public readonly int $threshold,
        public readonly int $taskWeight,
        public readonly bool $exceedsThreshold,
        public readonly Collection $alternatives,
    ) {}
}
