<?php

namespace App\Domain\Tasks\DTOs;

use App\Models\User;

final readonly class AlternativeCandidate
{
    public function __construct(
        public User $user,
        public int $workloadScore,
        public int $remainingCapacity,
        public ?float $remainingHours,
    ) {}
}
