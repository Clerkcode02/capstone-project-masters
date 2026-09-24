<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class OverAllocationDetected
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly int $workloadScore,
        public readonly int $threshold,
    ) {}
}
