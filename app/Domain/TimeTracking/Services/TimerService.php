<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\TimeLog;
use App\Models\User;

class TimerService
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
    ) {}

    public function activeTimer(User $user): ?TimeLog
    {
        return TimeLog::query()
            ->where('user_id', $user->id)
            ->where('entry_method', EntryMethod::Timer->value)
            ->whereNull('ended_at')
            ->first();
    }

    public function start(User $user, ?int $taskId, ?int $accountId, HourType $hourType = HourType::Production): TimeLog
    {
        $this->stop($user);

        return TimeLog::query()->create([
            'user_id' => $user->id,
            'task_id' => $taskId,
            'account_id' => $accountId,
            'log_date' => now()->toDateString(),
            'hour_type' => $hourType,
            'duration_minutes' => 0,
            'entry_method' => EntryMethod::Timer,
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }

    public function stop(User $user): ?TimeLog
    {
        $timer = $this->activeTimer($user);

        if (! $timer) {
            return null;
        }

        $endedAt = now();

        $timer->update([
            'ended_at' => $endedAt,
            'duration_minutes' => max(1, $timer->started_at->diffInMinutes($endedAt)),
        ]);

        $this->timeLogService->syncTaskActualHours($timer->task_id);

        return $timer;
    }
}
