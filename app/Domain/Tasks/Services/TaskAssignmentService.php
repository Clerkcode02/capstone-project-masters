<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\DTOs\WorkloadEvaluation;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Exceptions\OverAllocationWarning;
use App\Events\TaskAssigned;
use App\Models\Account;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creates a task and assigns it, running the pre-assignment over-allocation
 * check first (capstone Ch. 3, M3). Never silently blocks and never silently
 * allows: exceeding settings('workload_threshold') always surfaces the real
 * numbers via OverAllocationWarning, and a manager may still proceed — but
 * doing so is written to audit_logs as 'over_allocation_override'.
 */
class TaskAssignmentService
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    public function evaluate(Account $account, User $assignee, ComplexityTier $tier): WorkloadEvaluation
    {
        $weight = $this->weightFor($tier);
        $current = $this->workloadScoreService->scoreForUser($assignee);
        $prospective = $current + $weight;
        $threshold = $this->settings->int('workload_threshold', 12);
        $exceeds = $prospective > $threshold;

        return new WorkloadEvaluation(
            currentScore: $current,
            prospectiveScore: $prospective,
            threshold: $threshold,
            taskWeight: $weight,
            exceedsThreshold: $exceeds,
            alternatives: $exceeds ? $this->alternatives($account, $assignee, $threshold) : collect(),
        );
    }

    /**
     * @param  array{title: string, description: ?string, account_id: int, assigned_to: int, complexity_tier: string, standard_hours: float, due_date: ?string}  $data
     *
     * @throws OverAllocationWarning
     */
    public function assign(array $data, User $creator, bool $confirmedOverride = false): Task
    {
        $account = Account::query()->findOrFail($data['account_id']);
        $assignee = User::query()->findOrFail($data['assigned_to']);
        $tier = ComplexityTier::from($data['complexity_tier']);

        $evaluation = $this->evaluate($account, $assignee, $tier);

        if ($evaluation->exceedsThreshold && ! $confirmedOverride) {
            throw new OverAllocationWarning($evaluation);
        }

        return DB::transaction(function () use ($data, $creator, $assignee, $account, $tier, $evaluation) {
            $task = Task::create([
                'reference' => $this->nextReference(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'account_id' => $account->id,
                'assigned_to' => $assignee->id,
                'created_by' => $creator->id,
                'complexity_tier' => $tier->value,
                'complexity_weight' => $evaluation->taskWeight,
                'standard_hours' => $data['standard_hours'],
                'status' => TaskStatus::Pending->value,
                'due_date' => $data['due_date'] ?? null,
            ]);

            TaskAssigned::dispatch($task, $assignee, $creator, $evaluation->exceedsThreshold);

            return $task;
        });
    }

    private function weightFor(ComplexityTier $tier): int
    {
        return match ($tier) {
            ComplexityTier::Small => $this->settings->int('complexity_weight_small', 1),
            ComplexityTier::Medium => $this->settings->int('complexity_weight_medium', 3),
            ComplexityTier::Large => $this->settings->int('complexity_weight_large', 5),
        };
    }

    /**
     * @return Collection<int, array{user: User, score: int}>
     */
    private function alternatives(Account $account, User $exclude, int $threshold): Collection
    {
        return $account->users()
            ->where('is_active', true)
            ->where('users.id', '!=', $exclude->id)
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'score' => $this->workloadScoreService->scoreForUser($user),
            ])
            ->sortByDesc(fn (array $candidate) => $threshold - $candidate['score'])
            ->values()
            ->take(3);
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $sequence = Task::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('TSK-%d-%04d', $year, $sequence);
    }
}
