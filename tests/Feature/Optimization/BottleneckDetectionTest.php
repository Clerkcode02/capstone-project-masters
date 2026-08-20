<?php

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\RedistributionRecommendation;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

function optRole(): Role
{
    return Role::query()->firstOrCreate(
        ['name' => 'employee'],
        ['label' => 'Employee', 'description' => 'Individual contributor.'],
    );
}

function optAccount(string $name = 'Client Co', string $code = 'CLI-1'): Account
{
    return Account::query()->create(['name' => $name, 'code' => $code, 'is_active' => true]);
}

function optUser(string $firstName, string $lastName, bool $isActive = true): User
{
    static $sequence = 0;
    $sequence++;

    return User::query()->create([
        'employee_code' => "OPT-{$sequence}",
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => "opt{$sequence}@example.test",
        'password' => bcrypt('password'),
        'role_id' => optRole()->id,
        'designation_id' => Designation::query()->firstOrCreate(
            ['name' => 'Media Analyst'],
            ['utilization_target' => '0.800', 'is_active' => true],
        )->id,
        'is_active' => $isActive,
    ]);
}

function optTask(Account $account, User $assignee, User $creator, array $overrides = []): Task
{
    static $sequence = 0;
    $sequence++;

    return Task::query()->create(array_merge([
        'reference' => "OPT-TSK-{$sequence}",
        'title' => "Task {$sequence}",
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'created_by' => $creator->id,
        'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3,
        'standard_hours' => 8,
        'status' => TaskStatus::InProgress->value,
    ], $overrides));
}

function optLogProduction(User $user, Task $task, float $hours, string $date = '2026-08-10'): TimeLog
{
    return TimeLog::query()->create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'account_id' => $task->account_id,
        'log_date' => $date,
        'hour_type' => HourType::Production->value,
        'duration_minutes' => (int) round($hours * 60),
        'entry_method' => 'manual',
    ]);
}

function optCapacityMetric(User $user, string $tier, float $effectiveAvailability): CapacityMetric
{
    return CapacityMetric::query()->create([
        'user_id' => $user->id,
        'period_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 8,
        'period_quarter' => null,
        'h_base' => 160,
        'h_leave' => 0,
        'h_poss' => 160,
        'u_target' => 0.800,
        'h_thresh' => 128,
        'h_prod' => 128 - $effectiveAvailability,
        'h_non_prod' => 0,
        'performance_percentage' => 90,
        'performance_tier' => $tier,
        'effective_availability_hours' => $effectiveAvailability,
        'computed_at' => now(),
    ]);
}

it('flags a bottleneck against the account-history average and recommends the best-ranked candidate', function () {
    $account = optAccount();
    $manager = optUser('Rico', 'Dela Cruz');
    $fromUser = optUser('Jun', 'Reyes');
    $maria = optUser('Maria', 'Santos');
    $overloaded = optUser('Paolo', 'Ramos');
    $overTier = optUser('Liza', 'Torres');
    $atThreshold = optUser('Ben', 'Cruz');
    $inactive = optUser('Ana', 'Lim', isActive: false);

    foreach ([$fromUser, $maria, $overloaded, $overTier, $atThreshold, $inactive] as $user) {
        $account->users()->attach($user->id, ['assigned_at' => now()]);
    }

    // Historical basis: 3 completed Medium tasks on this account, avg 9.0h.
    foreach (range(1, 3) as $i) {
        optTask($account, $fromUser, $manager, [
            'reference' => "OPT-HIST-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 9.00,
        ]);
    }

    // Candidate workload: Maria has plenty of headroom, Paolo is overloaded,
    // Liza is over her performance tier, Ben sits exactly at the threshold.
    optTask($account, $maria, $manager, ['complexity_weight' => 4, 'reference' => 'OPT-MARIA-1']);
    optTask($account, $overloaded, $manager, ['complexity_weight' => 12, 'reference' => 'OPT-PAOLO-1']);
    optTask($account, $overTier, $manager, ['complexity_weight' => 2, 'reference' => 'OPT-LIZA-1']);
    optTask($account, $atThreshold, $manager, ['complexity_weight' => 12, 'reference' => 'OPT-BEN-1']);

    optCapacityMetric($maria, 'acceptable', 22.0);
    optCapacityMetric($overloaded, 'acceptable', 3.0);
    optCapacityMetric($overTier, 'over', 40.0);
    optCapacityMetric($atThreshold, 'acceptable', 10.0);

    $bottleneckTask = optTask($account, $fromUser, $manager);
    optLogProduction($fromUser, $bottleneckTask, 14.5);

    $detector = new BottleneckDetectionService(new SettingsService);
    $result = $detector->evaluate($bottleneckTask);

    expect($result->isBottleneck)->toBeTrue()
        ->and($result->basis)->toBe(BottleneckBasis::AccountHistory)
        ->and($result->actualHours)->toBe(14.5)
        ->and($result->historicalAvgHours)->toBe(9.0)
        ->and($result->variancePercentage)->toBe(61.11)
        ->and($bottleneckTask->fresh()->is_bottleneck)->toBeTrue();

    $recommender = new RedistributionRecommender(new WorkloadScoreService, new SettingsService);
    $recommendation = $recommender->recommend($result);

    expect($recommendation)->not->toBeNull()
        ->and($recommendation->suggested_user_id)->toBe($maria->id)
        ->and((int) $recommendation->suggested_workload_score)->toBe(4)
        ->and($recommendation->status)->toBe(RecommendationStatus::Pending)
        ->and($recommendation->reason)->toContain('Maria Santos has 4 of 12 workload points and 22.0h of remaining capacity');
});

it('falls back to tier history, then to standard_hours, when account history is thin', function () {
    $account = optAccount('Other Account', 'OTH-1');
    $otherAccount = optAccount('Another Account', 'OTH-2');
    $manager = optUser('Rico', 'Dela Cruz');
    $fromUser = optUser('Jun', 'Reyes');

    // No history at all yet: cold start -> standard_hours.
    $task = optTask($account, $fromUser, $manager, ['standard_hours' => 10]);
    optLogProduction($fromUser, $task, 20);

    $detector = new BottleneckDetectionService(new SettingsService);
    $coldStart = $detector->evaluate($task);

    expect($coldStart->basis)->toBe(BottleneckBasis::StandardHours)
        ->and($coldStart->historicalAvgHours)->toBe(10.0);

    // Two completed tasks on a *different* account (same tier) -> tier history,
    // since fewer than 3 samples exist for this task's own account.
    foreach (range(1, 2) as $i) {
        optTask($otherAccount, $fromUser, $manager, [
            'reference' => "OPT-TIER-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 6.00,
        ]);
    }

    $tierFallback = $detector->evaluate($task);

    expect($tierFallback->basis)->toBe(BottleneckBasis::TierHistory)
        ->and($tierFallback->historicalAvgHours)->toBe(6.0);
});

it('does not create a duplicate pending recommendation when detection reruns', function () {
    $account = optAccount();
    $manager = optUser('Rico', 'Dela Cruz');
    $fromUser = optUser('Jun', 'Reyes');
    $maria = optUser('Maria', 'Santos');
    $account->users()->attach($maria->id, ['assigned_at' => now()]);

    foreach (range(1, 3) as $i) {
        optTask($account, $fromUser, $manager, [
            'reference' => "OPT-HIST2-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 9.00,
        ]);
    }

    $task = optTask($account, $fromUser, $manager);
    optLogProduction($fromUser, $task, 14.5);

    $detector = new BottleneckDetectionService(new SettingsService);
    $recommender = new RedistributionRecommender(new WorkloadScoreService, new SettingsService);

    $first = $recommender->recommend($detector->evaluate($task));
    $second = $recommender->recommend($detector->evaluate($task));

    expect($first->id)->toBe($second->id)
        ->and(RedistributionRecommendation::query()->count())->toBe(1);
});

it('leaves every task.assigned_to unchanged after optimization:detect runs', function () {
    $account = optAccount();
    $manager = optUser('Rico', 'Dela Cruz');
    $fromUser = optUser('Jun', 'Reyes');
    $maria = optUser('Maria', 'Santos');
    $account->users()->attach($maria->id, ['assigned_at' => now()]);
    optCapacityMetric($maria, 'acceptable', 22.0);

    foreach (range(1, 3) as $i) {
        optTask($account, $fromUser, $manager, [
            'reference' => "OPT-HIST3-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 9.00,
        ]);
    }

    $task = optTask($account, $fromUser, $manager);
    optLogProduction($fromUser, $task, 14.5);

    $assignmentsBefore = Task::query()->pluck('assigned_to', 'id');

    $this->artisan('optimization:detect')->assertExitCode(0);

    $assignmentsAfter = Task::query()->pluck('assigned_to', 'id');

    expect($assignmentsAfter->all())->toBe($assignmentsBefore->all())
        ->and(RedistributionRecommendation::query()->count())->toBe(1);
});
