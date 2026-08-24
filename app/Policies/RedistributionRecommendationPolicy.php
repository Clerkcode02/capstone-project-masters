<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\RedistributionRecommendation;
use App\Models\User;

class RedistributionRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::Manager) || $user->isRole(Role::Administrator);
    }

    public function view(User $user, RedistributionRecommendation $recommendation): bool
    {
        return $this->viewAny($user);
    }

    public function review(User $user, RedistributionRecommendation $recommendation): bool
    {
        return $this->viewAny($user);
    }
}
