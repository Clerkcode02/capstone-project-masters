<?php

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

function logProduction(User $user, Task $task, float $hours): void
{
    TimeLog::create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'account_id' => $task->account_id,
        'log_date' => now()->toDateString(),
        'hour_type' => HourType::Production->value,
        'duration_minutes' => (int) round($hours * 60),
        'entry_method' => 'manual',
    ]);
}

test('it recommends the best-ranked candidate and persists the full evidence set', function () {
    $account = Account::factory()->create(['name' => 'Account X']);

    $assignee = User::factory()->create();
    $account->users()->attach($assignee->id, ['assigned_at' => now()]);

    // Three completed reference tasks on the same account+tier to establish an account_history average of 9.0h.
    foreach ([8.0, 9.0, 10.0] as $hours) {
        Task::factory()->completed()->create([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium->value,
            'actual_hours' => $hours,
        ]);
    }

    $task = Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3,
        'standard_hours' => 9.00,
    ]);

    logProduction($assignee, $task, 14.5);

    // Candidate A: best remaining capacity, should be ranked first.
    $candidateA = User::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
    $account->users()->attach($candidateA->id, ['assigned_at' => now()]);
    Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $candidateA->id,
        'complexity_weight' => 4,
    ]);
    CapacityMetric::factory()->create([
        'user_id' => $candidateA->id,
        'performance_tier' => PerformanceTier::Acceptable->value,
        'effective_availability_hours' => 22.00,
    ]);

    // Candidate B: less remaining capacity than A, should rank second.
    $candidateB = User::factory()->create();
    $account->users()->attach($candidateB->id, ['assigned_at' => now()]);
    Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $candidateB->id,
        'complexity_weight' => 8,
    ]);
    CapacityMetric::factory()->create([
        'user_id' => $candidateB->id,
        'performance_tier' => PerformanceTier::Acceptable->value,
        'effective_availability_hours' => 10.00,
    ]);

    // Candidate C: over target, must be excluded.
    $candidateOver = User::factory()->create();
    $account->users()->attach($candidateOver->id, ['assigned_at' => now()]);
    CapacityMetric::factory()->create([
        'user_id' => $candidateOver->id,
        'performance_tier' => PerformanceTier::Over->value,
        'effective_availability_hours' => 30.00,
    ]);

    // Candidate D: at/over the workload threshold, must be excluded.
    $candidateFull = User::factory()->create();
    $account->users()->attach($candidateFull->id, ['assigned_at' => now()]);
    Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $candidateFull->id,
        'complexity_weight' => 12,
    ]);

    // Candidate E: different account entirely, must be excluded.
    $otherAccount = Account::factory()->create();
    $candidateElsewhere = User::factory()->create();
    $otherAccount->users()->attach($candidateElsewhere->id, ['assigned_at' => now()]);

    Artisan::call('optimization:detect');

    $task->refresh();
    expect($task->is_bottleneck)->toBeTrue();

    $recommendation = RedistributionRecommendation::query()->where('task_id', $task->id)->sole();

    expect($recommendation->from_user_id)->toBe($assignee->id)
        ->and($recommendation->suggested_user_id)->toBe($candidateA->id)
        ->and((float) $recommendation->actual_hours)->toBe(14.5)
        ->and((float) $recommendation->historical_avg_hours)->toBe(9.0)
        ->and((float) $recommendation->variance_percentage)->toBe(61.11)
        ->and($recommendation->from_workload_score)->toBe(3)
        ->and($recommendation->suggested_workload_score)->toBe(7)
        ->and($recommendation->basis->value)->toBe('account_history')
        ->and($recommendation->status)->toBe(RecommendationStatus::Pending)
        ->and($recommendation->reason)->toBe(
            'This task has consumed 14.5h against a 9.0h historical average for Medium tasks on Account X (+61%). '
            .'Maria Santos has 4 of 12 workload points and 22.0h of remaining capacity this month.'
        );
});

test('it does not create a duplicate pending recommendation for a task that already has one', function () {
    $account = Account::factory()->create();
    $assignee = User::factory()->create();
    $account->users()->attach($assignee->id, ['assigned_at' => now()]);

    foreach ([8.0, 9.0, 10.0] as $hours) {
        Task::factory()->completed()->create([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium->value,
            'actual_hours' => $hours,
        ]);
    }

    $task = Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3,
        'standard_hours' => 9.00,
    ]);

    logProduction($assignee, $task, 14.5);

    Artisan::call('optimization:detect');
    Artisan::call('optimization:detect');

    expect(RedistributionRecommendation::query()->where('task_id', $task->id)->count())->toBe(1);
});

test('it does not raise a recommendation for a task that is not a bottleneck', function () {
    $account = Account::factory()->create();
    $assignee = User::factory()->create();
    $account->users()->attach($assignee->id, ['assigned_at' => now()]);

    foreach ([8.0, 9.0, 10.0] as $hours) {
        Task::factory()->completed()->create([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium->value,
            'actual_hours' => $hours,
        ]);
    }

    $task = Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3,
        'standard_hours' => 9.00,
    ]);

    // Within the 25% variance threshold — not a bottleneck.
    logProduction($assignee, $task, 9.5);

    Artisan::call('optimization:detect');

    $task->refresh();
    expect($task->is_bottleneck)->toBeFalse();
    expect(RedistributionRecommendation::query()->where('task_id', $task->id)->exists())->toBeFalse();
});
