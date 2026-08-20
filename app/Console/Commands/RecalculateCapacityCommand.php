<?php

namespace App\Console\Commands;

use App\Domain\Capacity\Exceptions\MissingBaselineException;
use App\Domain\Capacity\Services\CapacityCalculationService;
use App\Models\CapacityMetric;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class RecalculateCapacityCommand extends Command
{
    protected $signature = 'capacity:recalculate {--year=} {--month=} {--user=}';

    protected $description = 'Recompute monthly (and, when applicable, quarterly) capacity_metrics rows.';

    public function handle(CapacityCalculationService $service): int
    {
        $year = (int) ($this->option('year') ?? now()->year);
        $month = (int) ($this->option('month') ?? now()->month);
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('designation_id')
            ->when($userId, fn ($query) => $query->where('id', $userId))
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No active, designated users matched the given filters.');

            return self::SUCCESS;
        }

        $rows = new Collection;

        foreach ($users as $user) {
            try {
                $metric = $service->calculateMonthly($user, $year, $month);
            } catch (MissingBaselineException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            if ($metric !== null) {
                $rows->push($metric);
            }
        }

        $quarter = (int) ceil($month / 3);

        if ($month % 3 === 0) {
            foreach ($users as $user) {
                $quarterlyMetric = $service->calculateQuarterly($user, $year, $quarter);

                if ($quarterlyMetric !== null) {
                    $rows->push($quarterlyMetric);
                }
            }
        }

        $this->renderSummary($rows);

        $this->info("Recalculated {$rows->count()} capacity_metrics row(s).");

        return self::SUCCESS;
    }

    private function renderSummary(Collection $rows): void
    {
        $rows->loadMissing('user');

        $this->table(
            ['User', 'Period', 'H_poss', 'H_thresh', 'H_prod', 'Performance %', 'Tier'],
            $rows->map(fn (CapacityMetric $row) => [
                $row->user?->full_name ?? "User #{$row->user_id}",
                $row->period_type === 'monthly'
                    ? Carbon::create($row->period_year, $row->period_month, 1)->format('Y-m')
                    : "{$row->period_year} Q{$row->period_quarter}",
                number_format((float) $row->h_poss, 2),
                number_format((float) $row->h_thresh, 2),
                number_format((float) $row->h_prod, 2),
                $row->performance_percentage !== null ? number_format((float) $row->performance_percentage, 2) : 'N/A',
                $row->performance_tier->label(),
            ])->all(),
        );
    }
}
