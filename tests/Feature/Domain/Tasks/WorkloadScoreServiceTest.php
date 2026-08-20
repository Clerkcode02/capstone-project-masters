<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Models\Account;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeWorkloadFixture(): array
{
    $role = Role::create(['name' => 'employee', 'label' => 'Employee']);
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);
    $user = User::create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
    ]);

    return [$account, $user];
}

function makeTaskFixture(Account $account, ?User $assignee, ComplexityTier $tier, int $weight, TaskStatus $status, string $reference): Task
{
    $creator = User::create([
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'email' => strtolower($reference).'@example.com',
        'password' => 'secret',
        'role_id' => Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager'])->id,
    ]);

    return Task::create([
        'reference' => $reference,
        'title' => 'Weekly media clipping report',
        'account_id' => $account->id,
        'assigned_to' => $assignee?->id,
        'created_by' => $creator->id,
        'complexity_tier' => $tier,
        'complexity_weight' => $weight,
        'standard_hours' => '4.00',
        'status' => $status,
    ]);
}

it('sums complexity weight only for in_progress tasks', function () {
    [$account, $user] = makeWorkloadFixture();

    makeTaskFixture($account, $user, ComplexityTier::Medium, 3, TaskStatus::InProgress, 'TSK-2026-0001');
    makeTaskFixture($account, $user, ComplexityTier::Large, 5, TaskStatus::InProgress, 'TSK-2026-0002');
    makeTaskFixture($account, $user, ComplexityTier::Large, 5, TaskStatus::Pending, 'TSK-2026-0003');
    makeTaskFixture($account, $user, ComplexityTier::Large, 5, TaskStatus::Completed, 'TSK-2026-0004');
    makeTaskFixture($account, $user, ComplexityTier::Small, 1, TaskStatus::Cancelled, 'TSK-2026-0005');

    expect(app(WorkloadScoreService::class)->score($user->fresh()))->toBe(8);
});

it('uses the complexity weight snapshotted on the task, not a live setting lookup', function () {
    [$account, $user] = makeWorkloadFixture();

    $task = makeTaskFixture($account, $user, ComplexityTier::Large, 5, TaskStatus::InProgress, 'TSK-2026-0006');

    // Snapshot changes, live settings do not affect the already-created task.
    $task->update(['complexity_weight' => 7]);

    expect(app(WorkloadScoreService::class)->score($user->fresh()))->toBe(7);
});

it('returns zero for a user with no in_progress tasks', function () {
    [, $user] = makeWorkloadFixture();

    expect(app(WorkloadScoreService::class)->score($user))->toBe(0);
});
