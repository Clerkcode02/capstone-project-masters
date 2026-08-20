<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Models\TimeLog;
use App\Models\User;

class ManualTimeEntryService
{
    /**
     * @param  array{task_id: ?int, account_id: ?int, log_date: string, hour_type: string, duration_minutes: int, notes: ?string}  $data
     */
    public function create(User $user, array $data): TimeLog
    {
        return TimeLog::create([
            'user_id' => $user->id,
            'task_id' => $data['task_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'log_date' => $data['log_date'],
            'hour_type' => $data['hour_type'],
            'duration_minutes' => $data['duration_minutes'],
            'entry_method' => EntryMethod::Manual->value,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
