<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TimerService
{
    /**
     * An employee has at most one running timer, kept in cache (not a table:
     * a running timer isn't a time_logs row yet since duration_minutes can't
     * be zero). The row is only written once the timer stops.
     *
     * @return array{started_at: string, account_id: int, task_id: int|null, hour_type: string}|null
     */
    public function active(User $user): ?array
    {
        return Cache::get($this->cacheKey($user));
    }

    public function start(User $user, Account $account, ?Task $task, HourType $hourType): void
    {
        if ($this->active($user) !== null) {
            throw new DomainException('A timer is already running for this user.');
        }

        Cache::put($this->cacheKey($user), [
            'started_at' => now()->toIso8601String(),
            'account_id' => $account->id,
            'task_id' => $task?->id,
            'hour_type' => $hourType->value,
        ], now()->addDay());
    }

    public function stop(User $user, ?string $notes = null): TimeLog
    {
        $state = $this->active($user);

        if ($state === null) {
            throw new DomainException('No timer is running for this user.');
        }

        $startedAt = Carbon::parse($state['started_at']);
        $endedAt = now();
        $minutes = min(1440, max(1, $startedAt->diffInMinutes($endedAt)));

        $timeLog = TimeLog::create([
            'user_id' => $user->id,
            'task_id' => $state['task_id'],
            'account_id' => $state['account_id'],
            'log_date' => $startedAt->toDateString(),
            'hour_type' => $state['hour_type'],
            'duration_minutes' => $minutes,
            'entry_method' => EntryMethod::Timer->value,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => $notes,
        ]);

        Cache::forget($this->cacheKey($user));

        return $timeLog;
    }

    private function cacheKey(User $user): string
    {
        return "time_tracking:active_timer:{$user->id}";
    }
}
