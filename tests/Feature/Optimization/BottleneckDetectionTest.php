<?php

use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use App\Models\Account;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\RedistributionRecommendation;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bottleneckAccount(string $code = 'AGL-001'): Account
{
    return Account::create(['name' => 'Agility PR Solutions', 'code' => $code]);
}

function bottleneckUser(string $email, ?Designation $designation = null): User
{
    $role = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);
    $designation ??= Designation::firstOrCreate(
        ['name' => 'Reports Analyst'],
        ['utilization_target' => '0.800']
    );

    return User::create([
        'first_name' => 'Test',
        'last_name' => 'Analyst',
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

function bottleneckTask(Account $account, User $assignee, User $creator, string $status, float $actualHours, string $tier = 'medium', int $weight = 3): Task
{
    static $sequence = 0;
    $sequence++;

    return Task::create([
        'reference' => sprintf('TSK-2026-%04d', $sequence),
        'title' => 'Weekly toning report',
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'created_by' => $creator->id,
        'complexity_tier' => $tier,
        'complexity_weight' => $weight,
        'standard_hours' => '8.00',
        'actual_hours' => number_format($actualHours, 2, '.', ''),
        'status' => $status,
    ]);
}

it('flags an overrun task using the account historical average and creates a recommendation', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');
    $candidate = bottleneckUser('candidate@example.com');
    $account->users()->attach([$assignee->id, $candidate->id]);

    // 3 completed samples on this account/tier averaging 10h.
    foreach ([9.0, 10.0, 11.0] as $hours) {
        bottleneckTask($account, $candidate, $manager, 'completed', $hours);
    }

    $overrun = bottleneckTask($account, $assignee, $manager, 'in_progress', 15.0);

    $flagged = app(BottleneckDetectionService::class)->detect();

    expect($flagged)->toHaveCount(1);
    expect($flagged->first()['basis'])->toBe('account_history');
    expect($flagged->first()['historical_avg'])->toBe(10.0);
    expect($flagged->first()['variance_percentage'])->toBe(50.0);
    expect($overrun->fresh()->is_bottleneck)->toBeTrue();

    $recommendation = app(RedistributionRecommender::class)->recommend(
        $overrun,
        15.0,
        10.0,
        50.0,
        'account_history',
    );

    expect($recommendation)->not->toBeNull();
    expect($recommendation->task_id)->toBe($overrun->id);
    expect($recommendation->from_user_id)->toBe($assignee->id);
    expect($recommendation->suggested_user_id)->toBe($candidate->id);
    expect($recommendation->status->value)->toBe('pending');
    expect($recommendation->reason)->toContain('15.0h')->toContain('10.0h');
});

it('falls back to the tier-wide average when fewer than 3 completed samples exist on the account', function () {
    $accountA = bottleneckAccount('AGL-001');
    $accountB = bottleneckAccount('AGL-002');
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');

    // Only 1 completed sample on accountA/medium — below the account_history threshold of 3.
    bottleneckTask($accountA, $assignee, $manager, 'completed', 8.0);

    // 3 completed medium samples elsewhere, averaging 6h.
    foreach ([5.0, 6.0, 7.0] as $hours) {
        bottleneckTask($accountB, $assignee, $manager, 'completed', $hours);
    }

    $overrun = bottleneckTask($accountA, $assignee, $manager, 'in_progress', 9.0);

    $flagged = app(BottleneckDetectionService::class)->detect();

    expect($flagged)->toHaveCount(1);
    expect($flagged->first()['basis'])->toBe('tier_history');
    // Tier-wide average includes every completed medium task regardless of
    // account: the 8h sample on accountA plus the 5/6/7h samples on accountB.
    expect($flagged->first()['historical_avg'])->toBe(6.5);
});

it('falls back to the task standard hours when no completed history exists at all', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');

    // standard_hours is 8.00 for every task created by bottleneckTask().
    $overrun = bottleneckTask($account, $assignee, $manager, 'in_progress', 12.0);

    $flagged = app(BottleneckDetectionService::class)->detect();

    expect($flagged)->toHaveCount(1);
    expect($flagged->first()['basis'])->toBe('standard_hours');
    expect($flagged->first()['historical_avg'])->toBe(8.0);
});

it('does not flag a task whose variance is within the configured threshold', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');

    foreach ([9.0, 10.0, 11.0] as $hours) {
        bottleneckTask($account, $assignee, $manager, 'completed', $hours);
    }

    // 10% over the 10h average — under the default 25% threshold.
    bottleneckTask($account, $assignee, $manager, 'in_progress', 11.0);

    expect(app(BottleneckDetectionService::class)->detect())->toHaveCount(0);
});

it('does not create a duplicate recommendation for a task that already has a pending one', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');
    $candidate = bottleneckUser('candidate@example.com');
    $account->users()->attach([$assignee->id, $candidate->id]);

    $overrun = bottleneckTask($account, $assignee, $manager, 'in_progress', 15.0);

    $recommender = app(RedistributionRecommender::class);
    $first = $recommender->recommend($overrun, 15.0, 10.0, 50.0, 'standard_hours');
    $second = $recommender->recommend($overrun, 15.0, 10.0, 50.0, 'standard_hours');

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect(RedistributionRecommendation::where('task_id', $overrun->id)->count())->toBe(1);
});

it('excludes an over-threshold or over-tier candidate from the suggestion', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');
    $overloadedCandidate = bottleneckUser('overloaded@example.com');
    $overPerformingCandidate = bottleneckUser('overperformer@example.com');
    $account->users()->attach([$assignee->id, $overloadedCandidate->id, $overPerformingCandidate->id]);

    // Overloaded candidate: workload score already at/above the default threshold (12).
    bottleneckTask($account, $overloadedCandidate, $manager, 'in_progress', 5.0, weight: 12);

    // Over-performing candidate: latest capacity metric tier is 'over'.
    CapacityMetric::create([
        'user_id' => $overPerformingCandidate->id,
        'period_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 8,
        'h_base' => 160,
        'h_leave' => 0,
        'h_poss' => 160,
        'u_target' => '0.800',
        'h_thresh' => 128,
        'h_prod' => 160,
        'h_non_prod' => 0,
        'performance_percentage' => 125.00,
        'performance_tier' => 'over',
        'effective_availability_hours' => -32,
        'computed_at' => now(),
    ]);

    $overrun = bottleneckTask($account, $assignee, $manager, 'in_progress', 15.0);

    $recommendation = app(RedistributionRecommender::class)->recommend($overrun, 15.0, 10.0, 50.0, 'standard_hours');

    expect($recommendation->suggested_user_id)->toBeNull();
    expect($recommendation->reason)->toContain('No teammate');
});

it('leaves every tasks.assigned_to unchanged when the detection command runs', function () {
    $account = bottleneckAccount();
    $manager = bottleneckUser('manager@example.com');
    $assignee = bottleneckUser('assignee@example.com');
    $candidate = bottleneckUser('candidate@example.com');
    $account->users()->attach([$assignee->id, $candidate->id]);

    foreach ([9.0, 10.0, 11.0] as $hours) {
        bottleneckTask($account, $candidate, $manager, 'completed', $hours);
    }

    $overrun = bottleneckTask($account, $assignee, $manager, 'in_progress', 15.0);

    $assignedBefore = Task::query()->pluck('assigned_to', 'id')->all();

    $this->artisan('optimization:detect')->assertExitCode(0);

    $assignedAfter = Task::query()->pluck('assigned_to', 'id')->all();

    expect($assignedAfter)->toBe($assignedBefore);
    expect($overrun->fresh()->assigned_to)->toBe($assignee->id);
    expect(RedistributionRecommendation::where('task_id', $overrun->id)->exists())->toBeTrue();
});
