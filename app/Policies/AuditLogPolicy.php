<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * The audit trail is administrator-only — it is the compliance record of
     * every state change, including who exported what.
     */
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::Administrator);
    }
}
