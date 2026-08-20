<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class RoleChanged
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly ?User $actor,
        public readonly string $fromRole,
        public readonly string $toRole,
    ) {}
}
