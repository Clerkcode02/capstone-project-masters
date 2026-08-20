<?php

namespace App\Domain\Optimization\DTOs;

use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Models\Task;

final readonly class BottleneckEvidence
{
    public function __construct(
        public Task $task,
        public float $actualHours,
        public ?float $historicalAvgHours,
        public ?float $variancePercentage,
        public BottleneckBasis $basis,
    ) {}
}
