<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Bottleneck sweep (capstone Ch. 3, M6). Compares each in-progress task's
 * logged hours against a historical average and flags tasks.is_bottleneck
 * when the overrun exceeds settings('bottleneck_variance_pct'). Read-only
 * with respect to task ownership — see .claude/skills/capacity-engine/SKILL.md
 * "The hard boundary": this service never writes tasks.assigned_to.
 */
class BottleneckDetectionService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return Collection<int, array{task: Task, actual_hours: float, historical_avg: float, variance_percentage: float, basis: string}>
     */
    public function detect(): Collection
    {
        $variancePct = $this->settings->decimal('bottleneck_variance_pct', 25);

        $flagged = collect();

        Task::query()
            ->where('status', TaskStatus::InProgress->value)
            ->where('actual_hours', '>', 0)
            ->each(function (Task $task) use ($variancePct, $flagged) {
                [$historicalAvg, $basis] = $this->historicalAverage($task);

                if ($historicalAvg <= 0) {
                    return;
                }

                $actualHours = (float) $task->actual_hours;
                $variancePercentage = round((($actualHours - $historicalAvg) / $historicalAvg) * 100, 2);

                if ($variancePercentage <= $variancePct) {
                    return;
                }

                if (! $task->is_bottleneck) {
                    $task->update(['is_bottleneck' => true]);
                }

                $flagged->push([
                    'task' => $task,
                    'actual_hours' => $actualHours,
                    'historical_avg' => $historicalAvg,
                    'variance_percentage' => $variancePercentage,
                    'basis' => $basis,
                ]);
            });

        return $flagged;
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function historicalAverage(Task $task): array
    {
        $accountAverageQuery = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('account_id', $task->account_id)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);

        if ((clone $accountAverageQuery)->count() >= 3) {
            return [round((float) $accountAverageQuery->avg('actual_hours'), 2), 'account_history'];
        }

        $tierAverageQuery = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);

        if ((clone $tierAverageQuery)->exists()) {
            return [round((float) $tierAverageQuery->avg('actual_hours'), 2), 'tier_history'];
        }

        return [(float) $task->standard_hours, 'standard_hours'];
    }
}
