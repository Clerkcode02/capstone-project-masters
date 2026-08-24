<?php

namespace App\Domain\Capacity\Services;

use App\Domain\Capacity\DTOs\PerformanceResult;
use App\Domain\Capacity\Enums\PerformanceTier;

/**
 * Pure function: Performance Categorization Scale (capstone Ch. 3, Step 5).
 * No side effects, no persistence — deterministic arithmetic only.
 */
class PerformanceTierResolver
{
    public function resolve(float $hThresh, float $hProd, float $perfBelowMax, float $perfOverMin): PerformanceResult
    {
        if ($hThresh <= 0) {
            return new PerformanceResult(percentage: null, tier: PerformanceTier::NotApplicable);
        }

        $percentage = round(($hProd / $hThresh) * 100, 2);

        return new PerformanceResult(percentage: $percentage, tier: $this->tierForPercentage($percentage, $perfBelowMax, $perfOverMin));
    }

    public function tierForPercentage(float $percentage, float $perfBelowMax, float $perfOverMin): PerformanceTier
    {
        if ($percentage < $perfBelowMax) {
            return PerformanceTier::Below;
        }

        if ($percentage <= $perfOverMin) {
            return PerformanceTier::Acceptable;
        }

        return PerformanceTier::Over;
    }
}
