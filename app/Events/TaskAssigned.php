<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class TaskAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
        public readonly User $actor,
        public readonly bool $wasOverAllocationOverride,
    ) {}
}
