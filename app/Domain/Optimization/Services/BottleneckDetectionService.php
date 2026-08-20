<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\DTOs\BottleneckDetectionResult;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Task;

class BottleneckDetectionService
{
    private const MIN_ACCOUNT_SAMPLES = 3;

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function detect(Task $task): BottleneckDetectionResult
    {
        $actualHours = (float) $task->actual_hours;
        [$historicalAvg, $basis] = $this->resolveHistoricalAverage($task);

        $variancePct = $historicalAvg > 0
            ? round((($actualHours - $historicalAvg) / $historicalAvg) * 100, 2)
            : 0.0;

        $isBottleneck = $variancePct > $this->settings->getFloat('bottleneck_variance_pct');

        $task->forceFill(['is_bottleneck' => $isBottleneck])->save();

        return new BottleneckDetectionResult(
            taskId: $task->id,
            actualHours: round($actualHours, 2),
            historicalAvgHours: round($historicalAvg, 2),
            variancePercentage: $variancePct,
            basis: $basis,
            isBottleneck: $isBottleneck,
        );
    }

    /**
     * @return array{0: float, 1: BottleneckBasis}
     */
    private function resolveHistoricalAverage(Task $task): array
    {
        $accountStats = $this->completedTaskStats($task->complexity_tier->value, $task->account_id, $task->id);

        if ($accountStats['count'] >= self::MIN_ACCOUNT_SAMPLES) {
            return [$accountStats['avg'], BottleneckBasis::AccountHistory];
        }

        $tierStats = $this->completedTaskStats($task->complexity_tier->value, null, $task->id);

        if ($tierStats['count'] > 0) {
            return [$tierStats['avg'], BottleneckBasis::TierHistory];
        }

        return [(float) $task->standard_hours, BottleneckBasis::StandardHours];
    }

    /**
     * @return array{count: int, avg: float}
     */
    private function completedTaskStats(string $tier, ?int $accountId, int $excludeTaskId): array
    {
        $query = Task::query()
            ->where('complexity_tier', $tier)
            ->where('status', TaskStatus::Completed->value)
            ->where('actual_hours', '>', 0)
            ->where('id', '!=', $excludeTaskId);

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        return [
            'count' => (clone $query)->count(),
            'avg' => (float) ($query->avg('actual_hours') ?? 0),
        ];
    }
}
