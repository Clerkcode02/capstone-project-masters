<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Workload score = sum of complexity weights on a user's in_progress tasks
 * (capstone Ch. 3). Uses tasks.complexity_weight, the value snapshotted at
 * creation, never a live settings lookup — historical scores must stay
 * reproducible if weights change.
 */
class WorkloadScoreService
{
    public function scoreForUser(User $user): int
    {
        return (int) Task::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::InProgress->value)
            ->sum('complexity_weight');
    }

    /**
     * @return Collection<int, array{user: User, score: int}>
     */
    public function scoresForActiveUsers(): Collection
    {
        $scoresByUser = Task::query()
            ->where('status', TaskStatus::InProgress->value)
            ->selectRaw('assigned_to, SUM(complexity_weight) AS score')
            ->groupBy('assigned_to')
            ->pluck('score', 'assigned_to');

        return User::query()
            ->where('is_active', true)
            ->with('designation')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'score' => (int) ($scoresByUser[$user->id] ?? 0),
            ]);
    }
}
