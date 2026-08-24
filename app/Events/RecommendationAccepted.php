<?php

namespace App\Events;

use App\Models\RedistributionRecommendation;
use App\Models\TaskReassignment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class RecommendationAccepted
{
    use Dispatchable;

    public function __construct(
        public readonly RedistributionRecommendation $recommendation,
        public readonly TaskReassignment $reassignment,
        public readonly User $reviewedBy,
    ) {}
}
