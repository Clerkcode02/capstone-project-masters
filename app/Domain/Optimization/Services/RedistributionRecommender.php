<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Optimization\DTOs\BottleneckEvidence;
use App\Domain\Optimization\DTOs\RankedCandidate;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Enums\TriggerType;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class RedistributionRecommender
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Persists a redistribution_recommendations row for one flagged task.
     * Never writes to tasks.assigned_to — reassignment only happens once a
     * manager explicitly accepts the recommendation elsewhere.
     */
    public function recommend(BottleneckEvidence $evidence): ?RedistributionRecommendation
    {
        $task = $evidence->task;

        if ($task->assigned_to === null) {
            return null;
        }

        $alreadyPending = RedistributionRecommendation::query()
            ->where('task_id', $task->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->exists();

        if ($alreadyPending) {
            return null;
        }

        $workloadThreshold = $this->settings->getInt('workload_threshold');
        $candidates = $this->rankedCandidates($task, $workloadThreshold);
        $topCandidate = $candidates->first();

        return RedistributionRecommendation::create([
            'task_id' => $task->id,
            'from_user_id' => $task->assigned_to,
            'suggested_user_id' => $topCandidate?->user->id,
            'trigger_type' => TriggerType::Bottleneck->value,
            'actual_hours' => round($evidence->actualHours, 2),
            'historical_avg_hours' => $evidence->historicalAvgHours !== null
                ? round($evidence->historicalAvgHours, 2)
                : null,
            'variance_percentage' => $evidence->variancePercentage !== null
                ? round($evidence->variancePercentage, 2)
                : null,
            'from_workload_score' => $this->workloadScoreService->score($task->assignee),
            'suggested_workload_score' => $topCandidate !== null
                ? $topCandidate->workloadScore + $task->complexity_weight
                : null,
            'basis' => $evidence->basis->value,
            'reason' => $this->buildReason($task, $evidence, $topCandidate, $workloadThreshold),
            'status' => RecommendationStatus::Pending->value,
        ]);
    }

    /**
     * Candidate filter + ranking per the capacity-engine spec section 4.
     *
     * @return Collection<int, RankedCandidate>
     */
    private function rankedCandidates(Task $task, int $workloadThreshold): Collection
    {
        $latestMonthlyMetricByUser = CapacityMetric::query()
            ->where('period_type', 'monthly')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $eligibleUsers = $task->account->users()
            ->where('users.is_active', true)
            ->where('users.id', '!=', $task->assigned_to)
            ->get();

        return $eligibleUsers
            ->map(function (User $user) use ($latestMonthlyMetricByUser): RankedCandidate {
                $metric = $latestMonthlyMetricByUser->get($user->id);

                return new RankedCandidate(
                    user: $user,
                    workloadScore: $this->workloadScoreService->score($user),
                    effectiveAvailabilityHours: $metric !== null
                        ? (float) $metric->effective_availability_hours
                        : null,
                    performanceTier: $metric?->performance_tier,
                );
            })
            ->filter(fn (RankedCandidate $candidate) => $candidate->workloadScore < $workloadThreshold)
            ->filter(fn (RankedCandidate $candidate) => $candidate->performanceTier !== PerformanceTier::Over)
            ->sort(function (RankedCandidate $a, RankedCandidate $b) use ($workloadThreshold): int {
                $remainingA = $workloadThreshold - $a->workloadScore;
                $remainingB = $workloadThreshold - $b->workloadScore;

                if ($remainingA !== $remainingB) {
                    return $remainingB <=> $remainingA;
                }

                return ($b->effectiveAvailabilityHours ?? -INF) <=> ($a->effectiveAvailabilityHours ?? -INF);
            })
            ->values()
            ->take(3);
    }

    private function buildReason(
        Task $task,
        BottleneckEvidence $evidence,
        ?RankedCandidate $candidate,
        int $workloadThreshold,
    ): string {
        $tierLabel = $task->complexity_tier->label();
        $accountName = $task->account->name;
        $actual = number_format($evidence->actualHours, 1);

        if ($evidence->historicalAvgHours !== null && $evidence->variancePercentage !== null) {
            $historical = number_format($evidence->historicalAvgHours, 1);
            $variance = sprintf('%+d%%', (int) round($evidence->variancePercentage));
            $taskSentence = "This task has consumed {$actual}h against a {$historical}h historical average "
                ."for {$tierLabel} tasks on {$accountName} ({$variance}).";
        } else {
            $taskSentence = "This task has consumed {$actual}h against the {$tierLabel} standard-hours "
                ."baseline on {$accountName}.";
        }

        if ($candidate === null) {
            return $taskSentence.' No eligible teammate currently has capacity for reassignment.';
        }

        $availability = $candidate->effectiveAvailabilityHours !== null
            ? number_format($candidate->effectiveAvailabilityHours, 1)
            : '0.0';

        $candidateSentence = "{$candidate->user->full_name} has {$candidate->workloadScore} of "
            ."{$workloadThreshold} workload points and {$availability}h of remaining capacity this month.";

        return $taskSentence.' '.$candidateSentence;
    }
}
