<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\DTOs\AlternativeCandidate;
use App\Domain\Tasks\DTOs\OverAllocationCheckResult;
use App\Events\TaskAssigned;
use App\Models\CapacityMetric;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskAssignmentService
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    public function evaluate(Task $task, User $candidate): OverAllocationCheckResult
    {
        $threshold = $this->settings->int('workload_threshold');
        $currentScore = $this->workloadScoreService->score($candidate);
        $prospectiveScore = $currentScore + $task->complexity_weight;
        $exceeds = $prospectiveScore > $threshold;

        return new OverAllocationCheckResult(
            candidate: $candidate,
            currentScore: $currentScore,
            prospectiveScore: $prospectiveScore,
            threshold: $threshold,
            exceedsThreshold: $exceeds,
            percentOverThreshold: $exceeds && $threshold > 0
                ? round((($prospectiveScore - $threshold) / $threshold) * 100, 2)
                : 0.0,
            alternatives: $exceeds ? $this->rankedAlternatives($task, $candidate, $threshold)->all() : [],
        );
    }

    public function assign(Task $task, User $candidate, User $actor, bool $override): void
    {
        $task->update(['assigned_to' => $candidate->id]);

        TaskAssigned::dispatch($task, $candidate, $actor, $override);
    }

    /**
     * @return Collection<int, AlternativeCandidate>
     */
    private function rankedAlternatives(Task $task, User $excluding, int $threshold): Collection
    {
        return $task->account->users()
            ->where('users.is_active', true)
            ->where('users.id', '!=', $excluding->id)
            ->get()
            ->map(fn (User $user) => new AlternativeCandidate(
                user: $user,
                workloadScore: $score = $this->workloadScoreService->score($user),
                remainingCapacity: $threshold - $score,
                remainingHours: $this->latestRemainingHours($user),
            ))
            ->filter(fn (AlternativeCandidate $candidate) => $candidate->remainingCapacity > 0)
            ->sortByDesc('remainingCapacity')
            ->values()
            ->take(3);
    }

    private function latestRemainingHours(User $user): ?float
    {
        $metric = CapacityMetric::query()
            ->where('user_id', $user->id)
            ->where('period_type', 'monthly')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->first();

        return $metric !== null ? (float) $metric->effective_availability_hours : null;
    }
}
