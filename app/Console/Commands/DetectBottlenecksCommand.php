<?php

namespace App\Console\Commands;

use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use App\Models\RedistributionRecommendation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DetectBottlenecksCommand extends Command
{
    protected $signature = 'optimization:detect';

    protected $description = 'Scan in-progress tasks for bottlenecks and record redistribution recommendations.';

    public function handle(BottleneckDetectionService $detector, RedistributionRecommender $recommender): int
    {
        $results = $detector->detect();
        $bottlenecks = $results->filter(fn ($result) => $result->isBottleneck);

        $recommendations = $bottlenecks
            ->map(fn ($result) => $recommender->recommend($result))
            ->filter();

        $this->renderSummary($recommendations);

        $this->info("Evaluated {$results->count()} in-progress task(s), flagged {$bottlenecks->count()} bottleneck(s), recorded {$recommendations->count()} recommendation(s).");

        return self::SUCCESS;
    }

    private function renderSummary(Collection $recommendations): void
    {
        if ($recommendations->isEmpty()) {
            return;
        }

        $this->table(
            ['Task', 'Actual h', 'Historical avg h', 'Variance %', 'Basis', 'Suggested'],
            $recommendations->map(fn (RedistributionRecommendation $recommendation) => [
                $recommendation->task->reference,
                number_format((float) $recommendation->actual_hours, 2),
                number_format((float) $recommendation->historical_avg_hours, 2),
                number_format((float) $recommendation->variance_percentage, 2).'%',
                $recommendation->basis,
                $recommendation->suggestedUser?->full_name ?? 'None available',
            ])->all(),
        );
    }
}
