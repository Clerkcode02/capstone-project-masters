<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Optimization\DTOs\BottleneckResult;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Enums\TriggerType;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\User;

class RedistributionRecommender
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Persists (or refreshes) a redistribution_recommendations row for a
     * bottlenecked task. Never touches tasks.assigned_to.
     */
    public function recommend(BottleneckResult $result): ?RedistributionRecommendation
    {
        if (! $result->isBottleneck) {
            return null;
        }

        $task = $result->task;
        $fromUser = $task->assignee;

        if ($fromUser === null) {
            return null;
        }

        $threshold = $this->settings->getInt('workload_threshold', 12);
        $fromScore = $this->workloadScoreService->score($fromUser);
        $candidates = $this->rankedCandidates($task, $fromUser, $threshold);
        $best = $candidates[0] ?? null;

        $attributes = [
            'suggested_user_id' => $best['user']->id ?? null,
            'trigger_type' => TriggerType::Bottleneck->value,
            'actual_hours' => $result->actualHours,
            'historical_avg_hours' => $result->historicalAvgHours,
            'variance_percentage' => $result->variancePercentage,
            'from_workload_score' => $fromScore,
            'suggested_workload_score' => $best['score'] ?? null,
            'basis' => $result->basis->value,
            'reason' => $this->buildReason($result, $task, $fromUser, $threshold, $best),
        ];

        $existing = RedistributionRecommendation::query()
            ->where('task_id', $task->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->first();

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return RedistributionRecommendation::query()->create([
            'task_id' => $task->id,
            'from_user_id' => $fromUser->id,
            'status' => RecommendationStatus::Pending->value,
            ...$attributes,
        ]);
    }

    /**
     * @return array<int, array{user: User, score: int, availability: float}>
     */
    private function rankedCandidates(Task $task, User $fromUser, int $threshold): array
    {
        $accountUserIds = $task->account->users()->pluck('users.id');

        $candidates = User::query()
            ->whereIn('id', $accountUserIds)
            ->where('is_active', true)
            ->where('id', '!=', $fromUser->id)
            ->get()
            ->map(function (User $user) {
                $metric = CapacityMetric::query()
                    ->where('user_id', $user->id)
                    ->where('period_type', 'monthly')
                    ->orderByDesc('period_year')
                    ->orderByDesc('period_month')
                    ->first();

                return [
                    'user' => $user,
                    'score' => $this->workloadScoreService->score($user),
                    'tier' => $metric?->performance_tier,
                    'availability' => $metric !== null ? (float) $metric->effective_availability_hours : 0.0,
                ];
            })
            ->filter(fn (array $candidate) => $candidate['score'] < $threshold)
            ->filter(fn (array $candidate) => $candidate['tier'] !== PerformanceTier::Over)
            ->all();

        usort($candidates, fn (array $a, array $b) => [$threshold - $b['score'], $b['availability']] <=> [$threshold - $a['score'], $a['availability']]);

        return array_slice($candidates, 0, 3);
    }

    /**
     * @param  array{user: User, score: int, availability: float}|null  $best
     */
    private function buildReason(BottleneckResult $result, Task $task, User $fromUser, int $threshold, ?array $best): string
    {
        $sign = $result->variancePercentage >= 0 ? '+' : '';
        $variance = $sign.number_format($result->variancePercentage, 0).'%';

        $evidence = sprintf(
            'This task has consumed %.1fh against a %.1fh historical average for %s tasks on %s (%s).',
            $result->actualHours,
            $result->historicalAvgHours,
            $task->complexity_tier->label(),
            $task->account->name,
            $variance,
        );

        if ($best === null) {
            return $evidence.' No teammate on this account currently has spare capacity; manager review required.';
        }

        $suggestion = sprintf(
            '%s has %d of %d workload points and %.1fh of remaining capacity this month.',
            $best['user']->full_name,
            $best['score'],
            $threshold,
            $best['availability'],
        );

        return "{$evidence} {$suggestion}";
    }
}
