<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\Services\WorkloadScoreService;
use Illuminate\Support\Collection;

/**
 * Builds the same per-employee workload scores shown on the Team Workload
 * chart, so the PDF/Excel exports and the screen never diverge.
 */
class TeamWorkloadReportService
{
    public function __construct(
        private readonly WorkloadScoreService $workloadScoreService,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array{scores: Collection<int, array{user_id: int, name: string, designation: ?string, score: int}>, threshold: int}
     */
    public function build(): array
    {
        $scores = $this->workloadScoreService
            ->scoresForActiveUsers()
            ->map(fn (array $entry) => [
                'user_id' => $entry['user']->id,
                'name' => $entry['user']->full_name,
                'designation' => $entry['user']->designation?->name,
                'score' => $entry['score'],
            ])
            ->sortByDesc('score')
            ->values();

        return [
            'scores' => $scores,
            'threshold' => $this->settings->int('workload_threshold', 12),
        ];
    }
}
