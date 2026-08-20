<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\DTOs\BottleneckResult;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Builder;

class BottleneckDetectionService
{
    private const MINIMUM_ACCOUNT_SAMPLES = 3;

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function evaluate(Task $task): BottleneckResult
    {
        $actualHours = $this->actualHours($task);
        [$historicalAvgHours, $basis] = $this->historicalAverage($task);

        $variancePercentage = $historicalAvgHours > 0
            ? round((($actualHours - $historicalAvgHours) / $historicalAvgHours) * 100, 2)
            : 0.0;

        $threshold = $this->settings->getDecimal('bottleneck_variance_pct', 25);
        $isBottleneck = $variancePercentage > $threshold;

        $task->update(['is_bottleneck' => $isBottleneck]);

        return new BottleneckResult(
            task: $task,
            actualHours: round($actualHours, 2),
            historicalAvgHours: round($historicalAvgHours, 2),
            variancePercentage: $variancePercentage,
            basis: $basis,
            isBottleneck: $isBottleneck,
        );
    }

    private function actualHours(Task $task): float
    {
        $minutes = TimeLog::query()
            ->where('task_id', $task->id)
            ->where('hour_type', HourType::Production->value)
            ->sum('duration_minutes');

        return $minutes / 60.0;
    }

    /**
     * @return array{0: float, 1: BottleneckBasis}
     */
    private function historicalAverage(Task $task): array
    {
        $accountTasks = $this->completedTasksQuery($task)
            ->where('account_id', $task->account_id)
            ->get();

        if ($accountTasks->count() >= self::MINIMUM_ACCOUNT_SAMPLES) {
            return [(float) $accountTasks->avg('actual_hours'), BottleneckBasis::AccountHistory];
        }

        $tierAvg = $this->completedTasksQuery($task)->avg('actual_hours');

        if ($tierAvg !== null) {
            return [(float) $tierAvg, BottleneckBasis::TierHistory];
        }

        return [(float) $task->standard_hours, BottleneckBasis::StandardHours];
    }

    private function completedTasksQuery(Task $task): Builder
    {
        return Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);
    }
}
