<?php

namespace App\Domain\Capacity\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\CapacityMetric;
use App\Models\MonthlyBaseline;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The six-step monthly capacity pipeline (capstone Ch. 3). Deterministic
 * arithmetic only — see .claude/skills/capacity-engine/SKILL.md.
 */
class CapacityCalculationService
{
    public function __construct(
        private readonly PerformanceTierResolver $tierResolver,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return Collection<int, CapacityMetric>
     */
    public function recalculateForMonth(int $year, int $month, ?int $userId = null): Collection
    {
        $baseline = MonthlyBaseline::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if (! $baseline) {
            throw new RuntimeException("No monthly baseline (H_base) configured for {$year}-{$month}. An administrator must set one before capacity can be calculated.");
        }

        $hBase = (float) $baseline->baseline_hours;

        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd = date('Y-m-t', strtotime($periodStart));

        $usersQuery = User::query()->with('designation')->where('is_active', true);

        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        $users = $usersQuery->get();

        $hoursByUser = DB::table('time_logs')
            ->select('user_id', 'hour_type', DB::raw('SUM(duration_minutes) / 60.0 AS hours'))
            ->whereBetween('log_date', [$periodStart, $periodEnd])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy('user_id', 'hour_type')
            ->get()
            ->groupBy('user_id');

        $perfBelowMax = $this->settings->decimal('perf_below_max', 90);
        $perfOverMin = $this->settings->decimal('perf_over_min', 110);

        $metrics = collect();

        foreach ($users as $user) {
            if (! $user->designation) {
                Log::warning("Skipping capacity calculation for user {$user->id}: no designation assigned.");

                continue;
            }

            $rows = $hoursByUser->get($user->id, collect());

            $hProd = (float) ($rows->firstWhere('hour_type', HourType::Production->value)->hours ?? 0);
            $hNonProd = (float) ($rows->firstWhere('hour_type', HourType::NonProduction->value)->hours ?? 0);
            $hLeave = (float) ($rows->firstWhere('hour_type', HourType::Leave->value)->hours ?? 0);

            $hPoss = round($hBase - $hLeave, 2);
            $uTarget = (float) $user->designation->utilization_target;
            $hThresh = round($hPoss * $uTarget, 2);

            $result = $this->tierResolver->resolve($hThresh, $hProd, $perfBelowMax, $perfOverMin);

            $metrics->push(CapacityMetric::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'period_type' => 'monthly',
                    'period_year' => $year,
                    'period_month' => $month,
                    'period_quarter' => null,
                ],
                [
                    'h_base' => round($hBase, 2),
                    'h_leave' => round($hLeave, 2),
                    'h_poss' => $hPoss,
                    'u_target' => $uTarget,
                    'h_thresh' => $hThresh,
                    'h_prod' => round($hProd, 2),
                    'h_non_prod' => round($hNonProd, 2),
                    'performance_percentage' => $result->percentage,
                    'performance_tier' => $result->tier,
                    'effective_availability_hours' => round($hThresh - $hProd, 2),
                    'computed_at' => now(),
                ]
            ));
        }

        return $metrics;
    }
}
