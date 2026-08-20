<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
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

    public function update(User $user, TimeLog $timeLog): bool
    {
        if ($timeLog->is_locked) {
            return false;
        }

        return $user->isRole(Role::Administrator) || $timeLog->user_id === $user->id;
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $this->update($user, $timeLog);
    }

    /**
     * Only managers and administrators may import production sheets on
     * behalf of the team.
     */
    public function import(User $user): bool
    {
        return $user->isRole(Role::Manager) || $user->isRole(Role::Administrator);
    }
}
