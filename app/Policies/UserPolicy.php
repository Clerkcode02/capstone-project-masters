<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isRole(Role::Administrator) || $user->id === $model->id) {
            return true;
        }

        return $user->isRole(Role::Manager) && $model->manager_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::Administrator);
    }

    public function update(User $user, User $model): bool
    {
        return $user->isRole(Role::Administrator) || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isRole(Role::Administrator) && $user->id !== $model->id;
    }
}
