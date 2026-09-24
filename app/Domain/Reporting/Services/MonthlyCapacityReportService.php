<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Models\CapacityMetric;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the same monthly capacity_metrics snapshot shown on the Monthly
 * At-a-Glance dashboard, so the PDF export and the screen never diverge.
 */
class MonthlyCapacityReportService
{
    /**
     * @return array{
     *     year: int, month: int, metrics: Collection<int, CapacityMetric>,
     *     teamSize: int, onTargetCount: int, overUtilizedCount: int, underUtilizedCount: int,
     * }
     */
    public function build(int $year, int $month, User $actor): array
    {
        $metrics = CapacityMetric::query()
            ->with(['user.designation'])
            ->where('period_type', 'monthly')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->visibleTo($actor)
            ->get()
            ->sortBy(fn (CapacityMetric $metric) => $metric->user?->full_name)
            ->values();

        return [
            'year' => $year,
            'month' => $month,
            'metrics' => $metrics,
            'teamSize' => $metrics->count(),
            'onTargetCount' => $metrics->where('performance_tier', PerformanceTier::Acceptable)->count(),
            'overUtilizedCount' => $metrics->where('performance_tier', PerformanceTier::Over)->count(),
            'underUtilizedCount' => $metrics->where('performance_tier', PerformanceTier::Below)->count(),
        ];
    }
}
