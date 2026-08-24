<?php

namespace App\Domain\Capacity\DTOs;

use App\Domain\Capacity\Enums\PerformanceTier;

class PerformanceResult
{
    public function __construct(
        public readonly ?float $percentage,
        public readonly PerformanceTier $tier,
    ) {}
}
