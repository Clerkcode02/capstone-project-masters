<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class TimerService
{
    public function start(User $user, int $accountId, ?int $taskId): TimeLog
    {
        abort_if(
            TimeLog::query()->where('user_id', $user->id)->whereNull('ended_at')->exists(),
            409,
            'A timer is already running.'
        );

        return TimeLog::create([
            'user_id' => $user->id,
            'task_id' => $taskId,
            'account_id' => $accountId,
            'log_date' => Carbon::now()->toDateString(),
            'hour_type' => HourType::Production->value,
            'duration_minutes' => 0,
            'entry_method' => EntryMethod::Timer->value,
            'started_at' => Carbon::now(),
        ]);
    }

    public function stop(TimeLog $timeLog, User $user): TimeLog
    {
        abort_unless($timeLog->user_id === $user->id, 403);
        abort_if($timeLog->ended_at !== null, 409, 'Timer already stopped.');

        $endedAt = Carbon::now();
        $minutes = max(1, (int) $timeLog->started_at->diffInMinutes($endedAt));

        $timeLog->update([
            'ended_at' => $endedAt,
            'duration_minutes' => min($minutes, 1440),
        ]);

        return $timeLog->refresh();
    }
}
