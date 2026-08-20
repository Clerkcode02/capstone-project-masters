<?php

namespace App\Domain\Optimization\DTOs;

use App\Domain\Optimization\Enums\BottleneckBasis;

final class BottleneckDetectionResult
{
    public function __construct(
        public readonly int $taskId,
        public readonly float $actualHours,
        public readonly float $historicalAvgHours,
        public readonly float $variancePercentage,
        public readonly BottleneckBasis $basis,
        public readonly bool $isBottleneck,
    ) {}
}
