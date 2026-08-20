<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['key' => 'complexity_weight_small', 'value' => '1', 'type' => 'int', 'group' => 'workload', 'label' => 'Small task complexity weight'],
            ['key' => 'complexity_weight_medium', 'value' => '3', 'type' => 'int', 'group' => 'workload', 'label' => 'Medium task complexity weight'],
            ['key' => 'complexity_weight_large', 'value' => '5', 'type' => 'int', 'group' => 'workload', 'label' => 'Large task complexity weight'],
            ['key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'workload', 'label' => 'Workload score threshold'],
            ['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below-tier performance ceiling (%)'],
            ['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over-tier performance floor (%)'],
            ['key' => 'bottleneck_variance_pct', 'value' => '25', 'type' => 'decimal', 'group' => 'optimization', 'label' => 'Bottleneck variance threshold (%)'],
            ['key' => 'timelog_edit_window_hours', 'value' => '48', 'type' => 'int', 'group' => 'timetracking', 'label' => 'Time log edit window (hours)'],
        ])->each(fn (array $setting) => Setting::query()->firstOrCreate(
            ['key' => $setting['key']],
            $setting,
        ));
    }
}
