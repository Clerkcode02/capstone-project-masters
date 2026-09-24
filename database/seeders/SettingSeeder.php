<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['key' => 'complexity_weight_small', 'value' => '1', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity weight — Small'],
            ['key' => 'complexity_weight_medium', 'value' => '3', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity weight — Medium'],
            ['key' => 'complexity_weight_large', 'value' => '5', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity weight — Large'],
            ['key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'workload', 'label' => 'Workload score warning threshold'],
            ['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Performance % below this is tier "below"'],
            ['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Performance % above this is tier "over"'],
            ['key' => 'bottleneck_variance_pct', 'value' => '25', 'type' => 'decimal', 'group' => 'optimization', 'label' => 'Variance over historical average that flags a bottleneck'],
            ['key' => 'timelog_edit_window_hours', 'value' => '48', 'type' => 'int', 'group' => 'timetracking', 'label' => 'Hours after logging a time entry it can still be edited'],
        ])->each(fn (array $setting) => Setting::query()->firstOrCreate(
            ['key' => $setting['key']],
            $setting
        ));
    }
}
