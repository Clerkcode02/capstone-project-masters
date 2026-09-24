<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Optimization\Enums\TriggerType;
use App\Models\RedistributionRecommendation;
use Illuminate\Support\Collection;

/**
 * Lists bottleneck-triggered redistribution_recommendations. Read-only —
 * this report never touches tasks.assigned_to; recommendations already
 * required a manager's explicit Accept before any reassignment happened.
 */
class BottleneckReportService
{
    /**
     * @return Collection<int, RedistributionRecommendation>
     */
    public function build(?string $fromDate = null, ?string $toDate = null): Collection
    {
        return RedistributionRecommendation::query()
            ->with(['task.account', 'fromUser', 'suggestedUser', 'reviewer'])
            ->where('trigger_type', TriggerType::Bottleneck->value)
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->orderByDesc('created_at')
            ->get();
    }
}
