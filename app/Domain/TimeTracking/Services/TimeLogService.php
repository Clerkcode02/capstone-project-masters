<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;

class TimeLogService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{log_date: string, task_id: ?int, account_id: ?int, hour_type: string, duration_minutes: int, notes: ?string}  $data
     */
    public function createManualEntry(User $user, array $data): TimeLog
    {
        $log = TimeLog::query()->create([
            'user_id' => $user->id,
            'task_id' => $data['task_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'log_date' => $data['log_date'],
            'hour_type' => $data['hour_type'],
            'duration_minutes' => $data['duration_minutes'],
            'entry_method' => EntryMethod::Manual,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncTaskActualHours($log->task_id);

        return $log;
    }

    /**
     * @param  array{log_date: string, task_id: ?int, account_id: ?int, hour_type: string, duration_minutes: int, notes: ?string}  $data
     */
    public function updateEntry(TimeLog $log, array $data): TimeLog
    {
        $previousTaskId = $log->task_id;

        $log->update([
            'task_id' => $data['task_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'log_date' => $data['log_date'],
            'hour_type' => $data['hour_type'],
            'duration_minutes' => $data['duration_minutes'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncTaskActualHours($previousTaskId);

        if ($log->task_id !== $previousTaskId) {
            $this->syncTaskActualHours($log->task_id);
        }

        return $log;
    }

    public function deleteEntry(TimeLog $log): void
    {
        $taskId = $log->task_id;

        $log->delete();

        $this->syncTaskActualHours($taskId);
    }

    public function canEdit(TimeLog $log): bool
    {
        if ($log->is_locked) {
            return false;
        }

        $windowHours = $this->settings->int('timelog_edit_window_hours');

        return $log->created_at->diffInHours(now()) < $windowHours;
    }

    public function syncTaskActualHours(?int $taskId): void
    {
        if ($taskId === null) {
            return;
        }

        $totalMinutes = TimeLog::query()
            ->where('task_id', $taskId)
            ->where('hour_type', HourType::Production->value)
            ->sum('duration_minutes');

        Task::query()->whereKey($taskId)->update([
            'actual_hours' => round($totalMinutes / 60, 2),
        ]);
    }
}
