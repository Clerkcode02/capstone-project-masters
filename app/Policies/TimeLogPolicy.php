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

    public function import(User $user): bool
    {
        return $user->isRole(Role::Manager) || $user->isRole(Role::Administrator);
    }
}
