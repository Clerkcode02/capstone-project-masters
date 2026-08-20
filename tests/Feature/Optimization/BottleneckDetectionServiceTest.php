<?php

use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bottleneckSetting(string $variancePct = '25'): void
{
    Setting::create([
        'key' => 'bottleneck_variance_pct',
        'value' => $variancePct,
        'type' => 'decimal',
        'group' => 'optimization',
        'label' => 'Bottleneck Variance %',
    ]);
}

function bottleneckAccount(string $code): Account
{
    return Account::create([
        'name' => "Account {$code}",
        'code' => $code,
        'is_active' => true,
    ]);
}

function bottleneckTask(array $attributes): Task
{
    static $reference = 0;
    $reference++;

    $creator = User::factory()->create();

    return Task::create(array_merge([
        'reference' => sprintf('TSK-TEST-%04d', $reference),
        'title' => 'Test Task',
        'created_by' => $creator->id,
        'complexity_weight' => match ($attributes['complexity_tier'] ?? ComplexityTier::Medium) {
            ComplexityTier::Small => 1,
            ComplexityTier::Large => 5,
            default => 3,
        },
        'standard_hours' => 8,
        'actual_hours' => 0,
        'status' => TaskStatus::InProgress,
    ], $attributes));
}

it('uses account history when the account has at least 3 completed same-tier samples', function () {
    bottleneckSetting();
    $account = bottleneckAccount('ACC-A');

    foreach ([10, 10, 10] as $hours) {
        bottleneckTask([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium,
            'status' => TaskStatus::Completed,
            'actual_hours' => $hours,
        ]);
    }

    $task = bottleneckTask([
        'account_id' => $account->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::InProgress,
        'actual_hours' => 12,
        'standard_hours' => 9,
    ]);

    $result = app(BottleneckDetectionService::class)->detect($task);

    expect($result->basis)->toBe(BottleneckBasis::AccountHistory)
        ->and($result->historicalAvgHours)->toBe(10.0)
        ->and($result->variancePercentage)->toBe(20.0)
        ->and($result->isBottleneck)->toBeFalse();
});

it('falls back to tier history when the account has fewer than 3 samples', function () {
    bottleneckSetting();
    $targetAccount = bottleneckAccount('ACC-B');
    $otherAccount = bottleneckAccount('ACC-C');

    // Only 2 completed same-tier samples on the target account (< 3 threshold).
    bottleneckTask([
        'account_id' => $targetAccount->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::Completed,
        'actual_hours' => 8,
    ]);
    bottleneckTask([
        'account_id' => $targetAccount->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::Completed,
        'actual_hours' => 8,
    ]);

    // Cross-account history for the same tier.
    bottleneckTask([
        'account_id' => $otherAccount->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::Completed,
        'actual_hours' => 10,
    ]);

    $task = bottleneckTask([
        'account_id' => $targetAccount->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::InProgress,
        'actual_hours' => 13.5,
    ]);

    $result = app(BottleneckDetectionService::class)->detect($task);

    // avg(8, 8, 10) = 8.67
    expect($result->basis)->toBe(BottleneckBasis::TierHistory)
        ->and($result->historicalAvgHours)->toBe(8.67);
});

it('falls back to standard_hours when there is no history at all (cold start)', function () {
    bottleneckSetting();
    $account = bottleneckAccount('ACC-D');

    $task = bottleneckTask([
        'account_id' => $account->id,
        'complexity_tier' => ComplexityTier::Large,
        'status' => TaskStatus::InProgress,
        'actual_hours' => 14,
        'standard_hours' => 10,
    ]);

    $result = app(BottleneckDetectionService::class)->detect($task);

    expect($result->basis)->toBe(BottleneckBasis::StandardHours)
        ->and($result->historicalAvgHours)->toBe(10.0)
        ->and($result->variancePercentage)->toBe(40.0)
        ->and($result->isBottleneck)->toBeTrue();

    expect($task->fresh()->is_bottleneck)->toBeTrue();
});

it('flags a task as a bottleneck when variance exceeds the configured threshold', function () {
    bottleneckSetting('25');
    $account = bottleneckAccount('ACC-E');

    foreach ([10, 10, 10] as $hours) {
        bottleneckTask([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium,
            'status' => TaskStatus::Completed,
            'actual_hours' => $hours,
        ]);
    }

    $task = bottleneckTask([
        'account_id' => $account->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::InProgress,
        'actual_hours' => 16, // +60% variance
    ]);

    $result = app(BottleneckDetectionService::class)->detect($task);

    expect($result->variancePercentage)->toBe(60.0)
        ->and($result->isBottleneck)->toBeTrue()
        ->and($task->fresh()->is_bottleneck)->toBeTrue();
});

it('does not flag a task under the variance threshold', function () {
    bottleneckSetting('25');
    $account = bottleneckAccount('ACC-F');

    foreach ([10, 10, 10] as $hours) {
        bottleneckTask([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium,
            'status' => TaskStatus::Completed,
            'actual_hours' => $hours,
        ]);
    }

    $task = bottleneckTask([
        'account_id' => $account->id,
        'complexity_tier' => ComplexityTier::Medium,
        'status' => TaskStatus::InProgress,
        'actual_hours' => 11, // +10% variance, under 25% threshold
    ]);

    $result = app(BottleneckDetectionService::class)->detect($task);

    expect($result->variancePercentage)->toBe(10.0)
        ->and($result->isBottleneck)->toBeFalse();

    expect($task->fresh()->is_bottleneck)->toBeFalse();
});
