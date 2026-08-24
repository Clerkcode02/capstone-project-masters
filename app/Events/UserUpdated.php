<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly ?User $actor,
    ) {}
}
