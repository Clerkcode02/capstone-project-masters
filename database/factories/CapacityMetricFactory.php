<?php

namespace Database\Factories;

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Models\CapacityMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapacityMetric>
 */
class CapacityMetricFactory extends Factory
{
    protected $model = CapacityMetric::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'period_type' => 'monthly',
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_quarter' => null,
            'h_base' => 160.00,
            'h_leave' => 0.00,
            'h_poss' => 160.00,
            'u_target' => 0.800,
            'h_thresh' => 128.00,
            'h_prod' => 100.00,
            'h_non_prod' => 20.00,
            'performance_percentage' => 78.13,
            'performance_tier' => PerformanceTier::Below->value,
            'effective_availability_hours' => 28.00,
            'computed_at' => now(),
        ];
    }
}
