<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\DTOs\BottleneckResult;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Collection;

class BottleneckDetectionService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return Collection<int, BottleneckResult>
     */
    public function detect(): Collection
    {
        return Task::query()
            ->active()
            ->get()
            ->map(fn (Task $task) => $this->evaluate($task));
    }

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
        $accountQuery = fn () => Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('account_id', $task->account_id)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id);

        if ($accountQuery()->count() >= 3) {
            return [(float) $accountQuery()->avg('actual_hours'), BottleneckBasis::AccountHistory];
        }

        $tierAvg = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('complexity_tier', $task->complexity_tier->value)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $task->id)
            ->avg('actual_hours');

        if ($tierAvg !== null) {
            return [(float) $tierAvg, BottleneckBasis::TierHistory];
        }

        return [(float) $task->standard_hours, BottleneckBasis::StandardHours];
    }
}
