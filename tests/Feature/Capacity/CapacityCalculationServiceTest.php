<?php

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Services\CapacityCalculationService;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function capacitySeedSettings(): void
{
    Setting::create(['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below max']);
    Setting::create(['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over min']);
}

function capacityUser(string $designationName, float $uTarget, string $email): User
{
    $role = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);
    $designation = Designation::firstOrCreate(
        ['name' => $designationName],
        ['utilization_target' => number_format($uTarget, 3, '.', '')]
    );

    return User::create([
        'first_name' => 'Test',
        'last_name' => $designationName,
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

function logHours(User $user, string $hourType, float $hours, string $date = '2026-06-15'): void
{
    TimeLog::create([
        'user_id' => $user->id,
        'log_date' => $date,
        'hour_type' => $hourType,
        'duration_minutes' => (int) round($hours * 60),
        'entry_method' => 'manual',
    ]);
}

it('produces H_poss, H_thresh, and the monthly percentage exactly per the manuscript formulas', function () {
    capacitySeedSettings();
    MonthlyBaseline::create(['period_year' => 2026, 'period_month' => 6, 'baseline_hours' => 160]);

    $analyst = capacityUser('Reports Analyst', 0.800, 'analyst@example.com');
    logHours($analyst, 'leave', 16);
    logHours($analyst, 'production', 100.80);

    $service = app(CapacityCalculationService::class);
    $metrics = $service->recalculateForMonth(2026, 6);

    $metric = $metrics->firstWhere('user_id', $analyst->id);

    expect((float) $metric->h_poss)->toBe(144.00);
    expect((float) $metric->h_thresh)->toBe(115.20);
    expect((float) $metric->performance_percentage)->toBe(87.50);
    expect($metric->performance_tier)->toBe(PerformanceTier::Below);
});

it('never divides by zero for an employee on full-month leave', function () {
    capacitySeedSettings();
    MonthlyBaseline::create(['period_year' => 2026, 'period_month' => 6, 'baseline_hours' => 160]);

    $employee = capacityUser('Reports Analyst', 0.800, 'onleave@example.com');
    logHours($employee, 'leave', 160);

    $metrics = app(CapacityCalculationService::class)->recalculateForMonth(2026, 6);
    $metric = $metrics->firstWhere('user_id', $employee->id);

    expect((float) $metric->h_thresh)->toBe(0.0);
    expect($metric->performance_percentage)->toBeNull();
    expect($metric->performance_tier)->toBe(PerformanceTier::NotApplicable);
});

it('throws instead of guessing when no monthly baseline is configured', function () {
    capacitySeedSettings();
    capacityUser('Reports Analyst', 0.800, 'nobaseline@example.com');

    app(CapacityCalculationService::class)->recalculateForMonth(2026, 7);
})->throws(RuntimeException::class);

it('snapshots u_target so a later settings change does not alter a stored metric', function () {
    capacitySeedSettings();
    MonthlyBaseline::create(['period_year' => 2026, 'period_month' => 6, 'baseline_hours' => 160]);

    $analyst = capacityUser('Reports Analyst', 0.800, 'snapshot@example.com');
    logHours($analyst, 'production', 100);

    app(CapacityCalculationService::class)->recalculateForMonth(2026, 6);

    $designation = Designation::where('name', 'Reports Analyst')->first();
    $designation->update(['utilization_target' => '0.900']);

    $metric = CapacityMetric::where('user_id', $analyst->id)->first();

    expect((float) $metric->u_target)->toBe(0.800);
});
