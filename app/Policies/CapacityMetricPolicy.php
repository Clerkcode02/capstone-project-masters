<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\CapacityMetric;
use App\Models\User;

class CapacityMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::Manager) || $user->isRole(Role::Administrator);
    }

    public function view(User $user, CapacityMetric $capacityMetric): bool
    {
        if ($user->isRole(Role::Employee)) {
            return $capacityMetric->user_id === $user->id;
        }

        return true;
    }

    public function recalculate(User $user): bool
    {
        return $user->isRole(Role::Manager) || $user->isRole(Role::Administrator);
    }
}
