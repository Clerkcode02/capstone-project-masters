<?php

namespace App\Events;

use App\Models\RedistributionRecommendation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class RecommendationDismissed
{
    use Dispatchable;

    public function __construct(
        public readonly RedistributionRecommendation $recommendation,
        public readonly User $reviewedBy,
    ) {}
}
