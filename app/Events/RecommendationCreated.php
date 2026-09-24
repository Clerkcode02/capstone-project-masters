<?php

namespace App\Events;

use App\Models\RedistributionRecommendation;
use Illuminate\Foundation\Events\Dispatchable;

class RecommendationCreated
{
    use Dispatchable;

    public function __construct(
        public readonly RedistributionRecommendation $recommendation,
    ) {}
}
