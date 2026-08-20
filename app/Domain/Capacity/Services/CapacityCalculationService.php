<?php

namespace App\Domain\Capacity\Services;

use App\Domain\Capacity\Enums\PeriodType;
use App\Domain\Capacity\Exceptions\MissingBaselineException;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\CapacityMetric;
use App\Models\MonthlyBaseline;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CapacityCalculationService
{
    public function __construct(
        private readonly PerformanceTierResolver $resolver,
    ) {}

    /**
     * Steps 1-5: ingest a user's time logs for one calendar month, compute
     * H_poss/H_thresh/performance percentage, and upsert the monthly
     * capacity_metrics row.
     *
     * @throws MissingBaselineException
     */
    public function calculateMonthly(User $user, int $year, int $month): ?CapacityMetric
    {
        $designation = $user->designation;

        if ($designation === null) {
            Log::warning('Skipping capacity calculation: user has no designation.', [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
            ]);

            return null;
        }

        $baseline = MonthlyBaseline::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($baseline === null) {
            throw MissingBaselineException::forPeriod($year, $month);
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $hours = $user->timeLogs()
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('hour_type, SUM(duration_minutes) / 60.0 AS hours')
            ->groupBy('hour_type')
            ->pluck('hours', 'hour_type');

        $hProd = (float) ($hours[HourType::Production->value] ?? 0);
        $hNonProd = (float) ($hours[HourType::NonProduction->value] ?? 0);
        $hLeave = (float) ($hours[HourType::Leave->value] ?? 0);

        $hBase = (float) $baseline->baseline_hours;
        $hPoss = $hBase - $hLeave;

        $uTarget = (float) $designation->utilization_target;
        $hThresh = $hPoss * $uTarget;

        $result = $this->resolver->resolve($hThresh, $hProd);

        return CapacityMetric::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'period_type' => PeriodType::Monthly->value,
                'period_year' => $year,
                'period_month' => $month,
                'period_quarter' => null,
            ],
            [
                'h_base' => round($hBase, 2),
                'h_leave' => round($hLeave, 2),
                'h_poss' => round($hPoss, 2),
                'u_target' => round($uTarget, 3),
                'h_thresh' => round($hThresh, 2),
                'h_prod' => round($hProd, 2),
                'h_non_prod' => round($hNonProd, 2),
                'performance_percentage' => $result->percentage,
                'performance_tier' => $result->tier->value,
                'effective_availability_hours' => $result->effectiveAvailabilityHours,
                'computed_at' => now(),
            ],
        );
    }

    /**
     * Step 6: aggregate the three monthly rows of a quarter into a single
     * quarterly capacity_metrics row. Requires all three monthly rows to
     * already be persisted (via calculateMonthly).
     */
    public function calculateQuarterly(User $user, int $year, int $quarter): ?CapacityMetric
    {
        $months = $this->monthsInQuarter($quarter);

        $monthlyRows = CapacityMetric::query()
            ->where('user_id', $user->id)
            ->where('period_type', PeriodType::Monthly->value)
            ->where('period_year', $year)
            ->whereIn('period_month', $months)
            ->get();

        if ($monthlyRows->count() !== 3) {
            return null;
        }

        $applicablePercentages = $monthlyRows
            ->pluck('performance_percentage')
            ->filter(fn (?string $percentage) => $percentage !== null)
            ->map(fn (string $percentage) => (float) $percentage);

        $quarterlyPercentage = $applicablePercentages->isNotEmpty()
            ? round($applicablePercentages->avg(), 2)
            : null;

        $tier = $this->resolver->tierForPercentage($quarterlyPercentage);

        $hPossSum = round($monthlyRows->sum(fn (CapacityMetric $row) => (float) $row->h_poss), 2);
        $hThreshSum = round($monthlyRows->sum(fn (CapacityMetric $row) => (float) $row->h_thresh), 2);
        $hProdSum = round($monthlyRows->sum(fn (CapacityMetric $row) => (float) $row->h_prod), 2);
        $hLeaveSum = round($monthlyRows->sum(fn (CapacityMetric $row) => (float) $row->h_leave), 2);
        $hNonProdSum = round($monthlyRows->sum(fn (CapacityMetric $row) => (float) $row->h_non_prod), 2);
        $effectiveAvailability = round($hThreshSum - $hProdSum, 2);

        $last = $monthlyRows->sortBy('period_month')->last();

        return CapacityMetric::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'period_type' => PeriodType::Quarterly->value,
                'period_year' => $year,
                'period_month' => null,
                'period_quarter' => $quarter,
            ],
            [
                'h_base' => round((float) $last->h_base, 2),
                'h_leave' => $hLeaveSum,
                'h_poss' => $hPossSum,
                'u_target' => round((float) $last->u_target, 3),
                'h_thresh' => $hThreshSum,
                'h_prod' => $hProdSum,
                'h_non_prod' => $hNonProdSum,
                'performance_percentage' => $quarterlyPercentage,
                'performance_tier' => $tier->value,
                'effective_availability_hours' => $effectiveAvailability,
                'computed_at' => now(),
            ],
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function monthsInQuarter(int $quarter): array
    {
        $lastMonth = $quarter * 3;

        return [$lastMonth - 2, $lastMonth - 1, $lastMonth];
    }
}
