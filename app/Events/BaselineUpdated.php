<?php

namespace App\Events;

use App\Models\MonthlyBaseline;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BaselineUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly MonthlyBaseline $baseline,
        public readonly ?User $actor,
    ) {}
}
