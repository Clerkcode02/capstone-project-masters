<?php

namespace App\Domain\Accounts\Events;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AccountAudited
{
    use Dispatchable;

    public function __construct(
        public readonly Account $account,
        public readonly User $actor,
        public readonly string $action,
        public readonly string $description,
    ) {}
}
