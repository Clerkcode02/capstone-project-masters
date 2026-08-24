<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isRole(Role::Employee)) {
            return $task->assigned_to === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isRole(Role::Administrator) || $user->isRole(Role::Manager)) {
            return true;
        }

        return $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }
}
