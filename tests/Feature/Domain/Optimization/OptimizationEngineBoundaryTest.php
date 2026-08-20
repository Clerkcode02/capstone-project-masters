<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

/**
 * Non-negotiable: the optimization engine may only ever write rows into
 * redistribution_recommendations. It must never assign a task. This test
 * must never be deleted.
 */
test('optimization:detect never changes tasks.assigned_to', function () {
    $this->seed(SettingSeeder::class);

    $account = Account::factory()->create();

    $assignee = User::factory()->create();
    $account->users()->attach($assignee->id, ['assigned_at' => now()]);

    $candidate = User::factory()->create();
    $account->users()->attach($candidate->id, ['assigned_at' => now()]);

    // Historical reference tasks so an account-history average exists.
    foreach ([8.0, 9.0, 10.0] as $hours) {
        Task::factory()->completed()->create([
            'account_id' => $account->id,
            'complexity_tier' => ComplexityTier::Medium->value,
            'actual_hours' => $hours,
        ]);
    }

    // A clear bottleneck: 14.5h actual against a 9.0h historical average.
    $bottleneckTask = Task::factory()->inProgress()->create([
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'complexity_tier' => ComplexityTier::Medium->value,
        'complexity_weight' => 3,
        'standard_hours' => 9.00,
    ]);

    TimeLog::create([
        'user_id' => $assignee->id,
        'task_id' => $bottleneckTask->id,
        'account_id' => $account->id,
        'log_date' => now()->toDateString(),
        'hour_type' => HourType::Production->value,
        'duration_minutes' => (int) round(14.5 * 60),
        'entry_method' => 'manual',
    ]);

    // A handful of other in-progress and unassigned tasks to widen the sweep.
    Task::factory()->inProgress()->create(['account_id' => $account->id, 'assigned_to' => $candidate->id]);
    Task::factory()->create(['account_id' => $account->id, 'assigned_to' => null]);
    Task::factory()->create(['account_id' => $account->id, 'assigned_to' => $assignee->id]);

    $snapshot = Task::query()->withTrashed()->pluck('assigned_to', 'id');

    Artisan::call('optimization:detect');
    Artisan::call('optimization:detect');

    $after = Task::query()->withTrashed()->pluck('assigned_to', 'id');

    expect($after->toArray())->toBe($snapshot->toArray());

    // Sanity check the sweep actually did something, so this test cannot
    // pass merely because the command was a no-op.
    expect(RedistributionRecommendation::query()->count())->toBeGreaterThan(0);
});
