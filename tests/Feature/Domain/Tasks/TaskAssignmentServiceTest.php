<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Services\TaskAssignmentService;
use App\Events\TaskAssigned;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function seedWorkloadThreshold(int $value = 12): Setting
{
    return Setting::create([
        'key' => 'workload_threshold',
        'value' => (string) $value,
        'type' => 'int',
        'group' => 'workload',
        'label' => 'Workload Threshold',
    ]);
}

function makeAssignmentFixture(): array
{
    $role = Role::create(['name' => 'employee', 'label' => 'Employee']);
    $managerRole = Role::create(['name' => 'manager', 'label' => 'Manager']);
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $manager = User::create([
        'first_name' => 'Maria', 'last_name' => 'Santos',
        'email' => 'maria.santos@example.com', 'password' => 'secret',
        'role_id' => $managerRole->id,
    ]);

    $candidate = User::create([
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com', 'password' => 'secret',
        'role_id' => $role->id, 'designation_id' => $designation->id,
    ]);

    $account->users()->attach($candidate->id, ['assigned_at' => now()]);

    $task = Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Weekly media clipping report',
        'account_id' => $account->id,
        'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Large,
        'complexity_weight' => 5,
        'standard_hours' => '4.00',
        'status' => TaskStatus::Pending,
    ]);

    return compact('account', 'manager', 'candidate', 'task');
}

it('flags when a prospective assignment would exceed the workload threshold', function () {
    seedWorkloadThreshold(12);
    ['candidate' => $candidate, 'task' => $task, 'account' => $account, 'manager' => $manager] = makeAssignmentFixture();

    // Give the candidate 10 existing in_progress workload points.
    Task::create([
        'reference' => 'TSK-2026-0002', 'title' => 'Existing task', 'account_id' => $account->id,
        'assigned_to' => $candidate->id, 'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Large, 'complexity_weight' => 10,
        'standard_hours' => '4.00', 'status' => TaskStatus::InProgress,
    ]);

    $result = app(TaskAssignmentService::class)->evaluate($task, $candidate);

    expect($result->currentScore)->toBe(10)
        ->and($result->prospectiveScore)->toBe(15)
        ->and($result->threshold)->toBe(12)
        ->and($result->exceedsThreshold)->toBeTrue()
        ->and($result->percentOverThreshold)->toBe(25.0);
});

it('does not flag a prospective assignment within the workload threshold', function () {
    seedWorkloadThreshold(12);
    ['candidate' => $candidate, 'task' => $task] = makeAssignmentFixture();

    $result = app(TaskAssignmentService::class)->evaluate($task, $candidate);

    expect($result->currentScore)->toBe(0)
        ->and($result->prospectiveScore)->toBe(5)
        ->and($result->exceedsThreshold)->toBeFalse()
        ->and($result->alternatives)->toBe([]);
});

it('ranks alternatives by remaining capacity descending, limited to three', function () {
    seedWorkloadThreshold(12);
    ['account' => $account, 'manager' => $manager, 'candidate' => $candidate, 'task' => $task] = makeAssignmentFixture();

    // Overload the primary candidate.
    Task::create([
        'reference' => 'TSK-2026-0002', 'title' => 'Existing task', 'account_id' => $account->id,
        'assigned_to' => $candidate->id, 'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Large, 'complexity_weight' => 10,
        'standard_hours' => '4.00', 'status' => TaskStatus::InProgress,
    ]);

    $names = ['Ana Reyes', 'Liza Cruz', 'Mark Villanueva', 'Rosa Garcia'];
    $scores = [2, 6, 9, 12];

    foreach ($names as $i => $name) {
        [$first, $last] = explode(' ', $name);
        $alt = User::create([
            'first_name' => $first, 'last_name' => $last,
            'email' => strtolower($first.'.'.$last).'@example.com', 'password' => 'secret',
            'role_id' => $candidate->role_id,
        ]);
        $account->users()->attach($alt->id, ['assigned_at' => now()]);

        if ($scores[$i] > 0) {
            Task::create([
                'reference' => 'TSK-ALT-'.$i, 'title' => 'Existing task', 'account_id' => $account->id,
                'assigned_to' => $alt->id, 'created_by' => $manager->id,
                'complexity_tier' => ComplexityTier::Large, 'complexity_weight' => $scores[$i],
                'standard_hours' => '4.00', 'status' => TaskStatus::InProgress,
            ]);
        }
    }

    $result = app(TaskAssignmentService::class)->evaluate($task, $candidate);

    expect($result->exceedsThreshold)->toBeTrue()
        ->and($result->alternatives)->toHaveCount(3);

    $remaining = array_map(fn ($a) => $a->remainingCapacity, $result->alternatives);
    expect($remaining)->toBe([10, 6, 3]); // 12-2, 12-6, 12-9; the 12-scored user has no remaining capacity and is excluded.
});

it('assigns the task and dispatches TaskAssigned with the override flag', function () {
    Event::fake();
    seedWorkloadThreshold(12);
    ['candidate' => $candidate, 'task' => $task, 'manager' => $manager] = makeAssignmentFixture();

    app(TaskAssignmentService::class)->assign($task, $candidate, $manager, override: true);

    expect($task->fresh()->assigned_to)->toBe($candidate->id);
    Event::assertDispatched(TaskAssigned::class, fn (TaskAssigned $e) => $e->task->is($task)
        && $e->assignee->is($candidate)
        && $e->actor->is($manager)
        && $e->wasOverAllocationOverride === true);
});

it('writes an audit log row when an over-allocation override occurs', function () {
    seedWorkloadThreshold(12);
    ['candidate' => $candidate, 'task' => $task, 'manager' => $manager] = makeAssignmentFixture();

    app(TaskAssignmentService::class)->assign($task, $candidate, $manager, override: true);

    $log = AuditLog::query()->where('auditable_id', $task->id)->where('auditable_type', Task::class)->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('over_allocation_override')
        ->and($log->user_id)->toBe($manager->id);
});

it('writes a plain task_assigned audit row when there was no override', function () {
    seedWorkloadThreshold(12);
    ['candidate' => $candidate, 'task' => $task, 'manager' => $manager] = makeAssignmentFixture();

    app(TaskAssignmentService::class)->assign($task, $candidate, $manager, override: false);

    $log = AuditLog::query()->where('auditable_id', $task->id)->where('auditable_type', Task::class)->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('task_assigned');
});
