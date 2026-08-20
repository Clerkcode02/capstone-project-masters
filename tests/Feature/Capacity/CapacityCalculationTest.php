<?php

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Exceptions\MissingBaselineException;
use App\Domain\Capacity\Services\CapacityCalculationService;
use App\Domain\Capacity\Services\PerformanceTierResolver;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRole(): Role
{
    return Role::query()->firstOrCreate(
        ['name' => 'employee'],
        ['label' => 'Employee', 'description' => 'Individual contributor.'],
    );
}

function makeDesignation(string $name, string $utilizationTarget): Designation
{
    return Designation::query()->create([
        'name' => $name,
        'utilization_target' => $utilizationTarget,
        'is_active' => true,
    ]);
}

function makeEmployee(?Designation $designation = null): User
{
    static $sequence = 0;
    $sequence++;

    return User::query()->create([
        'employee_code' => "EMP-{$sequence}",
        'first_name' => 'Maria',
        'last_name' => "Santos {$sequence}",
        'email' => "maria.santos{$sequence}@example.test",
        'password' => bcrypt('password'),
        'role_id' => makeRole()->id,
        'designation_id' => $designation?->id,
        'is_active' => true,
    ]);
}

function makeBaseline(int $year, int $month, string $hours): MonthlyBaseline
{
    return MonthlyBaseline::query()->create([
        'period_year' => $year,
        'period_month' => $month,
        'baseline_hours' => $hours,
    ]);
}

function logHours(User $user, HourType $type, int $year, int $month, float $hours): TimeLog
{
    return TimeLog::query()->create([
        'user_id' => $user->id,
        'log_date' => sprintf('%04d-%02d-15', $year, $month),
        'hour_type' => $type->value,
        'duration_minutes' => (int) round($hours * 60),
        'entry_method' => 'manual',
    ]);
}

function seedPerformanceSettings(): void
{
    Setting::query()->firstOrCreate(
        ['key' => 'perf_below_max'],
        ['value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below-tier performance ceiling (%)'],
    );
    Setting::query()->firstOrCreate(
        ['key' => 'perf_over_min'],
        ['value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over-tier performance floor (%)'],
    );
}

function capacityService(): CapacityCalculationService
{
    return new CapacityCalculationService(new PerformanceTierResolver(new SettingsService));
}

it('computes H_poss as H_base minus H_leave', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 16);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);

    expect((float) $metric->h_poss)->toBe(144.00);
});

it('computes H_thresh for an analyst designation at 0.80 target', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 16);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);

    expect((float) $metric->h_thresh)->toBe(115.20);
});

it('computes H_thresh for a team lead designation at 0.40 target', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Team Lead', '0.400');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 16);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);

    expect((float) $metric->h_thresh)->toBe(57.60);
});

it('computes the monthly performance percentage', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 16);
    logHours($user, HourType::Production, 2026, 1, 100.8);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);

    expect((float) $metric->performance_percentage)->toBe(87.50);
});

it('resolves 87.50% to the below tier', function () {
    seedPerformanceSettings();
    $resolver = new PerformanceTierResolver(new SettingsService);

    expect($resolver->tierForPercentage(87.50))->toBe(PerformanceTier::Below);
});

it('resolves the acceptable tier at both boundaries and mid-range', function () {
    seedPerformanceSettings();
    $resolver = new PerformanceTierResolver(new SettingsService);

    expect($resolver->tierForPercentage(100.00))->toBe(PerformanceTier::Acceptable)
        ->and($resolver->tierForPercentage(90.00))->toBe(PerformanceTier::Acceptable)
        ->and($resolver->tierForPercentage(110.00))->toBe(PerformanceTier::Acceptable);
});

it('resolves 110.01% to the over tier', function () {
    seedPerformanceSettings();
    $resolver = new PerformanceTierResolver(new SettingsService);

    expect($resolver->tierForPercentage(110.01))->toBe(PerformanceTier::Over);
});

it('marks a full-month leave as not_applicable without dividing by zero', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 160);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);

    expect((float) $metric->h_thresh)->toBe(0.0)
        ->and($metric->performance_percentage)->toBeNull()
        ->and($metric->performance_tier)->toBe(PerformanceTier::NotApplicable);
});

it('computes the quarterly mean of three monthly percentages', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);

    foreach ([1, 2, 3] as $month) {
        makeBaseline(2026, $month, '160.00');
    }

    // Month 1: H_thresh = 128, H_prod = 112 -> 87.50%... instead, seed each
    // month's stored metric directly so the quarterly mean is exactly
    // 87.5 / 102.0 / 95.5 as specified, independent of how H_thresh happens
    // to fall out of the H_poss/U_target combination.
    foreach ([1 => 87.5, 2 => 102.0, 3 => 95.5] as $month => $percentage) {
        CapacityMetric::query()->create([
            'user_id' => $user->id,
            'period_type' => 'monthly',
            'period_year' => 2026,
            'period_month' => $month,
            'period_quarter' => null,
            'h_base' => 160,
            'h_leave' => 0,
            'h_poss' => 160,
            'u_target' => 0.800,
            'h_thresh' => 128,
            'h_prod' => round(128 * $percentage / 100, 2),
            'h_non_prod' => 0,
            'performance_percentage' => $percentage,
            'performance_tier' => PerformanceTier::Acceptable->value,
            'effective_availability_hours' => 0,
            'computed_at' => now(),
        ]);
    }

    $quarterly = capacityService()->calculateQuarterly($user, 2026, 1);

    expect((float) $quarterly->performance_percentage)->toBe(95.00)
        ->and($quarterly->performance_tier)->toBe(PerformanceTier::Acceptable);
});

it('excludes a not_applicable month from the quarterly mean', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);

    CapacityMetric::query()->create([
        'user_id' => $user->id, 'period_type' => 'monthly', 'period_year' => 2026,
        'period_month' => 1, 'period_quarter' => null, 'h_base' => 160, 'h_leave' => 0,
        'h_poss' => 160, 'u_target' => 0.800, 'h_thresh' => 128, 'h_prod' => 112,
        'h_non_prod' => 0, 'performance_percentage' => 87.50,
        'performance_tier' => PerformanceTier::Below->value, 'effective_availability_hours' => 16,
        'computed_at' => now(),
    ]);
    CapacityMetric::query()->create([
        'user_id' => $user->id, 'period_type' => 'monthly', 'period_year' => 2026,
        'period_month' => 2, 'period_quarter' => null, 'h_base' => 160, 'h_leave' => 160,
        'h_poss' => 0, 'u_target' => 0.800, 'h_thresh' => 0, 'h_prod' => 0,
        'h_non_prod' => 0, 'performance_percentage' => null,
        'performance_tier' => PerformanceTier::NotApplicable->value, 'effective_availability_hours' => 0,
        'computed_at' => now(),
    ]);
    CapacityMetric::query()->create([
        'user_id' => $user->id, 'period_type' => 'monthly', 'period_year' => 2026,
        'period_month' => 3, 'period_quarter' => null, 'h_base' => 160, 'h_leave' => 0,
        'h_poss' => 160, 'u_target' => 0.800, 'h_thresh' => 128, 'h_prod' => 130.56,
        'h_non_prod' => 0, 'performance_percentage' => 102.00,
        'performance_tier' => PerformanceTier::Acceptable->value, 'effective_availability_hours' => -2.56,
        'computed_at' => now(),
    ]);

    $quarterly = capacityService()->calculateQuarterly($user, 2026, 1);

    expect((float) $quarterly->performance_percentage)->toBe(round((87.50 + 102.00) / 2, 2))
        ->and($quarterly->performance_tier)->toBe(PerformanceTier::Acceptable);
});

it('throws when no monthly_baselines row exists for the period', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);

    capacityService()->calculateMonthly($user, 2026, 1);
})->throws(MissingBaselineException::class);

it('counts only in_progress tasks toward the workload score', function () {
    $account = Account::query()->create(['name' => 'Client Co', 'code' => 'CLI-1', 'is_active' => true]);
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);

    Task::query()->create([
        'reference' => 'TSK-1', 'title' => 'In progress medium task', 'account_id' => $account->id,
        'assigned_to' => $user->id, 'created_by' => $user->id, 'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3, 'standard_hours' => 8, 'status' => TaskStatus::InProgress->value,
    ]);
    Task::query()->create([
        'reference' => 'TSK-2', 'title' => 'In progress large task', 'account_id' => $account->id,
        'assigned_to' => $user->id, 'created_by' => $user->id, 'complexity_tier' => ComplexityTier::Large->value,
        'complexity_weight' => 5, 'standard_hours' => 16, 'status' => TaskStatus::InProgress->value,
    ]);
    Task::query()->create([
        'reference' => 'TSK-3', 'title' => 'Completed task should not count', 'account_id' => $account->id,
        'assigned_to' => $user->id, 'created_by' => $user->id, 'complexity_tier' => ComplexityTier::Large->value,
        'complexity_weight' => 5, 'standard_hours' => 16, 'status' => TaskStatus::Completed->value,
    ]);
    Task::query()->create([
        'reference' => 'TSK-4', 'title' => 'Pending task should not count', 'account_id' => $account->id,
        'assigned_to' => $user->id, 'created_by' => $user->id, 'complexity_tier' => ComplexityTier::Small->value,
        'complexity_weight' => 1, 'standard_hours' => 2, 'status' => TaskStatus::Pending->value,
    ]);

    $score = (new WorkloadScoreService)->score($user);

    expect($score)->toBe(8);
});

it('keeps a stored metric unchanged after the designation target is later edited', function () {
    seedPerformanceSettings();
    $designation = makeDesignation('Media Analyst', '0.800');
    $user = makeEmployee($designation);
    makeBaseline(2026, 1, '160.00');
    logHours($user, HourType::Leave, 2026, 1, 16);
    logHours($user, HourType::Production, 2026, 1, 100.8);

    $metric = capacityService()->calculateMonthly($user, 2026, 1);
    $storedUTarget = (float) $metric->u_target;
    $storedHThresh = (float) $metric->h_thresh;

    $designation->update(['utilization_target' => '0.500']);

    $metric->refresh();

    expect((float) $metric->u_target)->toBe($storedUTarget)
        ->and((float) $metric->h_thresh)->toBe($storedHThresh)
        ->and((float) $metric->u_target)->not->toBe(0.500);
});

// The engine-boundary test ("optimization:detect leaves tasks.assigned_to
// unchanged") now lives in tests/Feature/Optimization/BottleneckDetectionTest.php
// alongside the rest of the optimization module's coverage.
