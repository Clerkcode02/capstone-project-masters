<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Account $account): bool
    {
        if ($user->isRole(Role::Administrator) || $user->isRole(Role::Manager)) {
            return true;
        }

        return $account->users()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::Administrator);
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isRole(Role::Administrator);
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->isRole(Role::Administrator);
    }

    public function manageAssignments(User $user, Account $account): bool
    {
        return $user->isRole(Role::Administrator);
    }
}
