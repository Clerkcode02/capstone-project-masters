<?php

namespace App\Domain\Optimization\DTOs;

use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Models\Task;

class BottleneckResult
{
    public function __construct(
        public readonly Task $task,
        public readonly float $actualHours,
        public readonly float $historicalAvgHours,
        public readonly float $variancePercentage,
        public readonly BottleneckBasis $basis,
        public readonly bool $isBottleneck,
    ) {}
}
