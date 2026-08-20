<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\RedistributionRecommendation;
use App\Models\User;

class RecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }

    public function view(User $user, RedistributionRecommendation $recommendation): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }

    public function accept(User $user, RedistributionRecommendation $recommendation): bool
    {
        return $user->isRole(Role::Administrator) || $user->isRole(Role::Manager);
    }

    public function reject(User $user, RedistributionRecommendation $recommendation): bool
    {
        return $this->accept($user, $recommendation);
    }
}
