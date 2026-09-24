<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Services\PerformanceTierResolver;
use App\Models\CapacityMetric;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the same employee-by-month-by-quarter matrix shown on the Quarterly
 * At-a-Glance dashboard, so the PDF export and the screen never diverge.
 */
class QuarterlyCapacityReportService
{
    public function __construct(
        private readonly PerformanceTierResolver $tierResolver,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array{
     *     year: int, quarter: int, months: array<int, int>,
     *     rows: Collection<int, array{user: User, months: Collection<int, ?CapacityMetric>, quarterly_percentage: ?float, quarterly_tier: PerformanceTier}>,
     * }
     */
    public function build(int $year, int $quarter, User $actor): array
    {
        $firstMonth = ($quarter - 1) * 3 + 1;
        $months = [$firstMonth, $firstMonth + 1, $firstMonth + 2];

        $metrics = CapacityMetric::query()
            ->with(['user.designation'])
            ->where('period_type', 'monthly')
            ->where('period_year', $year)
            ->whereIn('period_month', $months)
            ->visibleTo($actor)
            ->get()
            ->groupBy('user_id');

        $perfBelowMax = $this->settings->decimal('perf_below_max', 90);
        $perfOverMin = $this->settings->decimal('perf_over_min', 110);

        $rows = $metrics->isEmpty() ? collect() : $metrics
            ->map(function (Collection $userMetrics) use ($months, $perfBelowMax, $perfOverMin) {
                $user = $userMetrics->first()->user;

                $monthly = collect($months)->mapWithKeys(
                    fn (int $month) => [$month => $userMetrics->firstWhere('period_month', $month)]
                );

                $applicablePercentages = $monthly
                    ->filter(fn (?CapacityMetric $metric) => $metric && $metric->performance_tier !== PerformanceTier::NotApplicable)
                    ->map(fn (CapacityMetric $metric) => (float) $metric->performance_percentage);

                if ($applicablePercentages->isEmpty()) {
                    $quarterlyPercentage = null;
                    $quarterlyTier = PerformanceTier::NotApplicable;
                } else {
                    $quarterlyPercentage = round($applicablePercentages->avg(), 2);
                    $quarterlyTier = $this->tierResolver->tierForPercentage($quarterlyPercentage, $perfBelowMax, $perfOverMin);
                }

                return [
                    'user' => $user,
                    'months' => $monthly,
                    'quarterly_percentage' => $quarterlyPercentage,
                    'quarterly_tier' => $quarterlyTier,
                ];
            })
            ->sortBy(fn (array $row) => $row['user']?->full_name)
            ->values();

        return [
            'year' => $year,
            'quarter' => $quarter,
            'months' => $months,
            'rows' => $rows,
        ];
    }
}
