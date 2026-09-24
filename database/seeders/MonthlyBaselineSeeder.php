<?php

namespace Database\Seeders;

use App\Models\MonthlyBaseline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds H_base for the three complete months before the current one, plus
 * the current (in-progress) month, so the quarterly rollup and the live
 * dashboard both have data the moment the demo starts. H_base is the
 * standard 8-hour-day baseline for every weekday in the calendar month —
 * a real PR/media shop's expected working hours, not a fixed constant.
 */
class MonthlyBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $current = Carbon::now()->startOfMonth();

        for ($offset = 3; $offset >= 0; $offset--) {
            $period = $current->copy()->subMonths($offset);

            MonthlyBaseline::query()->firstOrCreate(
                [
                    'period_year' => $period->year,
                    'period_month' => $period->month,
                ],
                [
                    'baseline_hours' => $this->weekdayHours($period),
                ]
            );
        }
    }

    private function weekdayHours(Carbon $monthStart): float
    {
        $weekdays = 0;
        $cursor = $monthStart->copy()->startOfMonth();
        $end = $monthStart->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $weekdays++;
            }
            $cursor->addDay();
        }

        return round($weekdays * 8, 2);
    }
}
