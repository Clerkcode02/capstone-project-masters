<?php

namespace App\Console\Commands;

use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use Illuminate\Console\Command;

class DetectBottlenecks extends Command
{
    protected $signature = 'optimization:detect';

    protected $description = 'Scan in-progress tasks for bottlenecks and raise redistribution recommendations for managers to review.';

    public function handle(BottleneckDetectionService $detector, RedistributionRecommender $recommender): int
    {
        $flagged = $detector->detect();

        $created = 0;
        $rows = [];

        foreach ($flagged as $entry) {
            $recommendation = $recommender->recommend(
                $entry['task'],
                $entry['actual_hours'],
                $entry['historical_avg'],
                $entry['variance_percentage'],
                $entry['basis'],
            );

            if ($recommendation !== null) {
                $created++;
            }

            $rows[] = [
                $entry['task']->reference,
                $entry['task']->account->name,
                sprintf('%.1f%%', $entry['variance_percentage']),
                $entry['basis'],
                $recommendation !== null ? 'recommendation created' : 'skipped (already pending, or no candidate)',
            ];
        }

        if ($rows === []) {
            $this->info('No bottlenecks found.');

            return self::SUCCESS;
        }

        $this->table(['Task', 'Account', 'Variance', 'Basis', 'Result'], $rows);
        $this->info("{$flagged->count()} bottleneck task(s) found, {$created} new recommendation(s) created.");

        return self::SUCCESS;
    }
}
