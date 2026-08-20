<?php

namespace App\Domain\Capacity\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\DTOs\PerformanceResult;
use App\Domain\Capacity\Enums\PerformanceTier;

class PerformanceTierResolver
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * Steps 4-5: guard against division by zero, compute the performance
     * percentage, and map it to a tier.
     */
    public function resolve(float $hThresh, float $hProd): PerformanceResult
    {
        $effectiveAvailabilityHours = round($hThresh - $hProd, 2);

        if ($hThresh <= 0) {
            return new PerformanceResult(null, PerformanceTier::NotApplicable, $effectiveAvailabilityHours);
        }

        $percentage = round(($hProd / $hThresh) * 100, 2);

        return new PerformanceResult($percentage, $this->tierForPercentage($percentage), $effectiveAvailabilityHours);
    }

    /**
     * Step 5's boundary mapping in isolation, reused by the quarterly
     * aggregation once it has computed the mean percentage.
     */
    public function tierForPercentage(?float $percentage): PerformanceTier
    {
        if ($percentage === null) {
            return PerformanceTier::NotApplicable;
        }

        $below = $this->settings->getDecimal('perf_below_max', 90);
        $over = $this->settings->getDecimal('perf_over_min', 110);

        return match (true) {
            $percentage < $below => PerformanceTier::Below,
            $percentage <= $over => PerformanceTier::Acceptable,
            default => PerformanceTier::Over,
        };
    }
}
