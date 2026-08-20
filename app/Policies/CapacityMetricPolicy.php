<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\CapacityMetric;
use App\Models\User;

class CapacityMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Employees can never view another employee's capacity metrics.
     */
    public function view(User $user, CapacityMetric $capacityMetric): bool
    {
        if ($user->isRole(Role::Employee)) {
            return $capacityMetric->user_id === $user->id;
        }

        return true;
    }
}
