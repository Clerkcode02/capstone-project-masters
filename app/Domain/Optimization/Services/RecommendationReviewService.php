<?php

namespace App\Domain\Optimization\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Events\OverAllocationDetected;
use App\Events\RecommendationAccepted;
use App\Events\RecommendationDismissed;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\TaskReassignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecommendationReviewService
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    public function accept(RedistributionRecommendation $recommendation, User $reviewer, string $reason): TaskReassignment
    {
        return DB::transaction(function () use ($recommendation, $reviewer, $reason) {
            $recommendation = RedistributionRecommendation::query()
                ->whereKey($recommendation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($recommendation->status !== RecommendationStatus::Pending) {
                throw new RuntimeException('This recommendation has already been reviewed.');
            }

            if ($recommendation->suggested_user_id === null) {
                throw new RuntimeException('This recommendation has no suggested assignee to reassign to.');
            }

            $task = Task::query()->whereKey($recommendation->task_id)->lockForUpdate()->firstOrFail();

            $reassignment = TaskReassignment::create([
                'task_id' => $task->id,
                'from_user_id' => $recommendation->from_user_id,
                'to_user_id' => $recommendation->suggested_user_id,
                'recommendation_id' => $recommendation->id,
                'reason' => $reason,
                'performed_by' => $reviewer->id,
            ]);

            $task->update(['assigned_to' => $recommendation->suggested_user_id]);

            $recommendation->update([
                'status' => RecommendationStatus::Accepted,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            RecommendationAccepted::dispatch($recommendation, $reassignment, $reviewer);

            $this->checkOverAllocation($task->assigned_to);

            return $reassignment;
        });
    }

    private function checkOverAllocation(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $assignee = User::query()->find($userId);

        if ($assignee === null) {
            return;
        }

        $score = $this->workloadScoreService->scoreForUser($assignee);
        $threshold = $this->settings->int('workload_threshold', 12);

        if ($score > $threshold) {
            OverAllocationDetected::dispatch($assignee, $score, $threshold);
        }
    }

    public function dismiss(RedistributionRecommendation $recommendation, User $reviewer): void
    {
        DB::transaction(function () use ($recommendation, $reviewer) {
            $recommendation = RedistributionRecommendation::query()
                ->whereKey($recommendation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($recommendation->status !== RecommendationStatus::Pending) {
                throw new RuntimeException('This recommendation has already been reviewed.');
            }

            $recommendation->update([
                'status' => RecommendationStatus::Dismissed,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            RecommendationDismissed::dispatch($recommendation, $reviewer);
        });
    }
}
