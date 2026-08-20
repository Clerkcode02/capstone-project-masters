<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class WorkloadScoreService
{
    public function score(User $user): int
    {
        return (int) Task::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::InProgress->value)
            ->sum('complexity_weight');
    }
}
