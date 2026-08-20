<?php

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\Designation;
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

function bottleneckRole(): Role
{
    return Role::query()->firstOrCreate(
        ['name' => 'employee'],
        ['label' => 'Employee', 'description' => 'Individual contributor.'],
    );
}

function bottleneckAccount(string $name = 'Client Co', string $code = 'CLI-1'): Account
{
    return Account::query()->create(['name' => $name, 'code' => $code, 'is_active' => true]);
}

function bottleneckUser(string $firstName, string $lastName): User
{
    static $sequence = 0;
    $sequence++;

    return User::query()->create([
        'employee_code' => "BTL-{$sequence}",
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => "btl{$sequence}@example.test",
        'password' => bcrypt('password'),
        'role_id' => bottleneckRole()->id,
        'designation_id' => Designation::query()->firstOrCreate(
            ['name' => 'Reports Analyst'],
            ['utilization_target' => '0.800', 'is_active' => true],
        )->id,
        'is_active' => true,
    ]);
}

function bottleneckTask(Account $account, User $assignee, User $creator, array $overrides = []): Task
{
    static $sequence = 0;
    $sequence++;

    return Task::query()->create(array_merge([
        'reference' => "BTL-TSK-{$sequence}",
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

function bottleneckLogProduction(User $user, Task $task, float $hours, string $date = '2026-08-10'): TimeLog
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

it('uses the account history basis when at least three completed samples exist on the account', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('Rico', 'Dela Cruz');
    $assignee = bottleneckUser('Jun', 'Reyes');

    foreach (range(1, 3) as $i) {
        bottleneckTask($account, $assignee, $manager, [
            'reference' => "BTL-HIST-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 9.00,
        ]);
    }

    $task = bottleneckTask($account, $assignee, $manager);
    bottleneckLogProduction($assignee, $task, 14.5);

    $result = (new BottleneckDetectionService(new SettingsService))->evaluate($task);

    expect($result->basis)->toBe(BottleneckBasis::AccountHistory)
        ->and($result->actualHours)->toBe(14.5)
        ->and($result->historicalAvgHours)->toBe(9.0)
        ->and($result->variancePercentage)->toBe(61.11)
        ->and($result->isBottleneck)->toBeTrue()
        ->and($task->fresh()->is_bottleneck)->toBeTrue();
});

it('falls back to tier history when fewer than three samples exist on the account', function () {
    $account = bottleneckAccount('Home Account', 'HOM-1');
    $otherAccount = bottleneckAccount('Other Account', 'OTH-1');
    $manager = bottleneckUser('Rico', 'Dela Cruz');
    $assignee = bottleneckUser('Jun', 'Reyes');

    // Only two completed samples on this task's own account -> below the
    // account-history minimum, so the tier-wide average across accounts is used.
    foreach (range(1, 2) as $i) {
        bottleneckTask($account, $assignee, $manager, [
            'reference' => "BTL-ACC-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 8.00,
        ]);
    }

    foreach (range(1, 2) as $i) {
        bottleneckTask($otherAccount, $assignee, $manager, [
            'reference' => "BTL-TIER-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 6.00,
        ]);
    }

    $task = bottleneckTask($account, $assignee, $manager);
    bottleneckLogProduction($assignee, $task, 10);

    $result = (new BottleneckDetectionService(new SettingsService))->evaluate($task);

    // Tier-wide average across all four completed Medium tasks: (8+8+6+6)/4 = 7.0
    expect($result->basis)->toBe(BottleneckBasis::TierHistory)
        ->and($result->historicalAvgHours)->toBe(7.0);
});

it('falls back to standard_hours as a cold start when there is no completed history at all', function () {
    $account = bottleneckAccount('Fresh Account', 'FRS-1');
    $manager = bottleneckUser('Rico', 'Dela Cruz');
    $assignee = bottleneckUser('Jun', 'Reyes');

    $task = bottleneckTask($account, $assignee, $manager, ['standard_hours' => 10]);
    bottleneckLogProduction($assignee, $task, 20);

    $result = (new BottleneckDetectionService(new SettingsService))->evaluate($task);

    expect($result->basis)->toBe(BottleneckBasis::StandardHours)
        ->and($result->historicalAvgHours)->toBe(10.0)
        ->and($result->isBottleneck)->toBeTrue();
});

it('computes variance percentage correctly against the historical average', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('Rico', 'Dela Cruz');
    $assignee = bottleneckUser('Jun', 'Reyes');

    foreach (range(1, 3) as $i) {
        bottleneckTask($account, $assignee, $manager, [
            'reference' => "BTL-VAR-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 10.00,
        ]);
    }

    $task = bottleneckTask($account, $assignee, $manager);
    bottleneckLogProduction($assignee, $task, 12.5);

    $result = (new BottleneckDetectionService(new SettingsService))->evaluate($task);

    // ((12.5 - 10.0) / 10.0) * 100 = 25.00
    expect($result->variancePercentage)->toBe(25.0);
});

it('does not flag a task whose variance sits at or under the configured threshold', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('Rico', 'Dela Cruz');
    $assignee = bottleneckUser('Jun', 'Reyes');

    foreach (range(1, 3) as $i) {
        bottleneckTask($account, $assignee, $manager, [
            'reference' => "BTL-OK-{$i}",
            'status' => TaskStatus::Completed->value,
            'actual_hours' => 10.00,
        ]);
    }

    // Default bottleneck_variance_pct is 25; 10% overrun must not be flagged.
    $task = bottleneckTask($account, $assignee, $manager);
    bottleneckLogProduction($assignee, $task, 11);

    $result = (new BottleneckDetectionService(new SettingsService))->evaluate($task);

    expect($result->variancePercentage)->toBe(10.0)
        ->and($result->isBottleneck)->toBeFalse()
        ->and($task->fresh()->is_bottleneck)->toBeFalse();
});
