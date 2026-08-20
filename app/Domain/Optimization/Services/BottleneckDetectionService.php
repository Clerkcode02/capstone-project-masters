<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\DTOs\BottleneckEvidence;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Collection;

class BottleneckDetectionService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Sweeps every in-progress task, flags bottlenecks, and returns evidence
     * for each one. Never touches tasks.assigned_to.
     *
     * @return Collection<int, BottleneckEvidence>
     */
    public function detect(): Collection
    {
        $varianceThreshold = $this->settings->getFloat('bottleneck_variance_pct');

        $evidences = collect();

        Task::query()
            ->where('status', TaskStatus::InProgress->value)
            ->get()
            ->each(function (Task $task) use ($varianceThreshold, $evidences): void {
                $actualHours = $this->actualHours($task);
                [$historicalAvg, $basis] = $this->historicalAverage($task);

                $variancePct = null;
                $isBottleneck = false;

                if ($historicalAvg !== null && $historicalAvg > 0) {
                    $variancePct = round((($actualHours - $historicalAvg) / $historicalAvg) * 100, 2);
                    $isBottleneck = $variancePct > $varianceThreshold;
                }

                if ($task->is_bottleneck !== $isBottleneck) {
                    $task->forceFill(['is_bottleneck' => $isBottleneck])->save();
                }

                if ($isBottleneck) {
                    $evidences->push(new BottleneckEvidence(
                        task: $task,
                        actualHours: round($actualHours, 2),
                        historicalAvgHours: round($historicalAvg, 2),
                        variancePercentage: $variancePct,
                        basis: $basis,
                    ));
                }
            });

        return $evidences;
    }

    private function actualHours(Task $task): float
    {
        $minutes = TimeLog::query()
            ->where('task_id', $task->id)
            ->where('hour_type', HourType::Production->value)
            ->sum('duration_minutes');

        return $minutes / 60;
    }

    /**
     * @return array{0: float|null, 1: BottleneckBasis}
     */
    private function historicalAverage(Task $task): array
    {
        $accountQuery = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('account_id', $task->account_id)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);

        if ((clone $accountQuery)->count() >= 3) {
            return [(float) (clone $accountQuery)->avg('actual_hours'), BottleneckBasis::AccountHistory];
        }

        $tierQuery = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);

        if ((clone $tierQuery)->count() > 0) {
            return [(float) (clone $tierQuery)->avg('actual_hours'), BottleneckBasis::TierHistory];
        }

        return [(float) $task->standard_hours, BottleneckBasis::StandardHours];
    }
}
