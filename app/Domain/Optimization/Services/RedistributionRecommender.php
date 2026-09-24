<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Enums\TriggerType;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Turns a detected bottleneck into a self-explaining redistribution
 * recommendation (capstone Ch. 3, M6). Never touches tasks.assigned_to —
 * that only happens once a manager accepts, in
 * RecommendationReviewService::accept(). See
 * .claude/skills/capacity-engine/SKILL.md "The hard boundary".
 */
class RedistributionRecommender
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    public function recommend(Task $task, float $actualHours, float $historicalAvgHours, float $variancePercentage, string $basis): ?RedistributionRecommendation
    {
        $hasPending = RedistributionRecommendation::query()
            ->where('task_id', $task->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->exists();

        if ($hasPending) {
            return null;
        }

        $fromUser = $task->assignee;

        if ($fromUser === null) {
            return null;
        }

        $threshold = $this->settings->int('workload_threshold', 12);
        $fromScore = $this->workloadScoreService->scoreForUser($fromUser);

        $candidates = $this->rankedCandidates($task, $threshold);
        $suggested = $candidates->first();

        return RedistributionRecommendation::create([
            'task_id' => $task->id,
            'from_user_id' => $fromUser->id,
            'suggested_user_id' => $suggested['user']->id ?? null,
            'trigger_type' => TriggerType::Bottleneck->value,
            'actual_hours' => $actualHours,
            'historical_avg_hours' => $historicalAvgHours,
            'variance_percentage' => $variancePercentage,
            'from_workload_score' => $fromScore,
            'suggested_workload_score' => $suggested['score'] ?? null,
            'basis' => $basis,
            'reason' => $this->buildReason($task, $actualHours, $historicalAvgHours, $variancePercentage, $fromUser, $fromScore, $threshold, $suggested),
            'status' => RecommendationStatus::Pending->value,
        ]);
    }

    /**
     * @return Collection<int, array{user: User, score: int, availability: float}>
     */
    private function rankedCandidates(Task $task, int $threshold): Collection
    {
        return User::query()
            ->whereHas('accounts', fn ($query) => $query->where('accounts.id', $task->account_id))
            ->where('is_active', true)
            ->where('id', '!=', $task->assigned_to)
            ->get()
            ->map(function (User $user) {
                $latestMetric = CapacityMetric::query()
                    ->where('user_id', $user->id)
                    ->where('period_type', 'monthly')
                    ->orderByDesc('period_year')
                    ->orderByDesc('period_month')
                    ->first();

                return [
                    'user' => $user,
                    'score' => $this->workloadScoreService->scoreForUser($user),
                    'tier' => $latestMetric?->performance_tier,
                    'availability' => (float) ($latestMetric?->effective_availability_hours ?? 0.0),
                ];
            })
            ->filter(fn (array $candidate) => $candidate['score'] < $threshold && $candidate['tier'] !== PerformanceTier::Over)
            ->sort(function (array $a, array $b) use ($threshold) {
                $roomA = $threshold - $a['score'];
                $roomB = $threshold - $b['score'];

                return $roomA === $roomB
                    ? $b['availability'] <=> $a['availability']
                    : $roomB <=> $roomA;
            })
            ->values()
            ->take(3);
    }

    /**
     * @param  array{user: User, score: int, availability: float}|null  $suggested
     */
    private function buildReason(Task $task, float $actualHours, float $historicalAvgHours, float $variancePercentage, User $fromUser, int $fromScore, int $threshold, ?array $suggested): string
    {
        $sign = $variancePercentage >= 0 ? '+' : '';

        $overrun = sprintf(
            'This task has consumed %.1fh against a %.1fh historical average for %s tasks on %s (%s%.0f%%).',
            $actualHours,
            $historicalAvgHours,
            $task->complexity_tier->label(),
            $task->account->name,
            $sign,
            $variancePercentage,
        );

        if ($suggested === null) {
            return $overrun.' No teammate on this account is currently under the workload threshold; manual reassignment is recommended.';
        }

        $suggestion = sprintf(
            '%s has %d of %d workload points and %.1fh of remaining capacity this month.',
            $suggested['user']->full_name,
            $suggested['score'],
            $threshold,
            $suggested['availability'],
        );

        return "{$overrun} {$suggestion}";
    }
}
