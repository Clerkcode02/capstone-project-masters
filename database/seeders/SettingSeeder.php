<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['key' => 'complexity_weight_small', 'value' => '1', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity Weight — Small'],
            ['key' => 'complexity_weight_medium', 'value' => '3', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity Weight — Medium'],
            ['key' => 'complexity_weight_large', 'value' => '5', 'type' => 'int', 'group' => 'workload', 'label' => 'Complexity Weight — Large'],
            ['key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'workload', 'label' => 'Workload Threshold'],
            ['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Performance Below Max %'],
            ['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Performance Over Min %'],
            ['key' => 'bottleneck_variance_pct', 'value' => '25', 'type' => 'decimal', 'group' => 'optimization', 'label' => 'Bottleneck Variance %'],
            ['key' => 'timelog_edit_window_hours', 'value' => '48', 'type' => 'int', 'group' => 'timetracking', 'label' => 'Time Log Edit Window (Hours)'],
        ])->each(fn (array $setting) => Setting::query()->firstOrCreate(['key' => $setting['key']], $setting));
    }
}
