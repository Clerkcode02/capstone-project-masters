<?php

namespace App\Console\Commands;

use App\Domain\Capacity\Services\CapacityCalculationService;
use Illuminate\Console\Command;
use RuntimeException;

class RecalculateCapacity extends Command
{
    protected $signature = 'capacity:recalculate {--year=} {--month=} {--user=}';

    protected $description = 'Recompute monthly capacity_metrics for the given period (defaults to the current month).';

    public function handle(CapacityCalculationService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $month = (int) ($this->option('month') ?: now()->month);
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        try {
            $metrics = $service->recalculateForMonth($year, $month, $userId);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Recalculated capacity for {$metrics->count()} user(s) for {$year}-{$month}.");

        return self::SUCCESS;
    }
}
