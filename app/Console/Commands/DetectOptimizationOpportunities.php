<?php

namespace App\Console\Commands;

use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use Illuminate\Console\Command;

class DetectOptimizationOpportunities extends Command
{
    protected $signature = 'optimization:detect';

    protected $description = 'Sweep in-progress tasks for bottlenecks and generate redistribution recommendations.';

    public function handle(BottleneckDetectionService $detector, RedistributionRecommender $recommender): int
    {
        $evidences = $detector->detect();

        $created = 0;
        $skipped = 0;

        foreach ($evidences as $evidence) {
            if ($recommender->recommend($evidence) !== null) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->table(
            ['Bottleneck tasks found', 'Recommendations created', 'Skipped (duplicate or unassigned)'],
            [[$evidences->count(), $created, $skipped]],
        );

        return self::SUCCESS;
    }
}
