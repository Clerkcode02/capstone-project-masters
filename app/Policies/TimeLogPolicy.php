<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\TimeTracking\Services\TimeLogService;
use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Employees can never view another employee's raw time logs.
     */
    public function view(User $user, TimeLog $timeLog): bool
    {
        if ($user->isRole(Role::Employee)) {
            return $timeLog->user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Owners may edit their own logs only within the configured edit window;
     * after that, or once locked, the log is read-only. Administrators bypass
     * the window but never the lock (a locked period has already been
     * computed into capacity_metrics).
     */
    public function update(User $user, TimeLog $timeLog): bool
    {
        if ($timeLog->is_locked) {
            return false;
        }

        if ($timeLog->user_id !== $user->id && ! $user->isRole(Role::Administrator)) {
            return false;
        }

        if ($user->isRole(Role::Administrator)) {
            return true;
        }

        return $this->timeLogService->canEdit($timeLog);
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $this->update($user, $timeLog);
    }
}
