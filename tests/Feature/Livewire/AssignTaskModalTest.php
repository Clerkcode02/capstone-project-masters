<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Livewire\Tasks\AssignTaskModal;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function assignModalFixture(): array
{
    Setting::create([
        'key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'workload', 'label' => 'Workload Threshold',
    ]);

    $managerRole = Role::create(['name' => 'manager', 'label' => 'Manager']);
    $employeeRole = Role::create(['name' => 'employee', 'label' => 'Employee']);
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
        'role_id' => $employeeRole->id, 'designation_id' => $designation->id,
    ]);
    $account->users()->attach($candidate->id, ['assigned_at' => now()]);

    $task = Task::create([
        'reference' => 'TSK-2026-0001', 'title' => 'Weekly media clipping report',
        'account_id' => $account->id, 'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Large, 'complexity_weight' => 5,
        'standard_hours' => '4.00', 'status' => TaskStatus::Pending,
    ]);

    // Push the candidate to 10/12 workload points so this 5-point task overflows.
    Task::create([
        'reference' => 'TSK-2026-0002', 'title' => 'Existing task', 'account_id' => $account->id,
        'assigned_to' => $candidate->id, 'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Large, 'complexity_weight' => 10,
        'standard_hours' => '4.00', 'status' => TaskStatus::InProgress,
    ]);

    return compact('manager', 'candidate', 'task');
}

it('surfaces a warning rather than silently assigning past the workload threshold', function () {
    ['manager' => $manager, 'candidate' => $candidate, 'task' => $task] = assignModalFixture();

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['task' => $task])
        ->call('selectCandidate', $candidate->id)
        ->assertSet('showWarning', true)
        ->call('confirmAssign')
        ->assertSet('showWarning', true);

    expect($task->fresh()->assigned_to)->toBeNull();
    expect(AuditLog::query()->count())->toBe(0);
});

it('allows an override and writes an audit-logged over-allocation record', function () {
    ['manager' => $manager, 'candidate' => $candidate, 'task' => $task] = assignModalFixture();

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['task' => $task])
        ->call('selectCandidate', $candidate->id)
        ->call('confirmAssign', true);

    expect($task->fresh()->assigned_to)->toBe($candidate->id);

    $log = AuditLog::query()->where('auditable_id', $task->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('over_allocation_override')
        ->and($log->user_id)->toBe($manager->id);
});

it('assigns immediately without a warning when within the workload threshold', function () {
    ['manager' => $manager, 'candidate' => $candidate, 'task' => $task] = assignModalFixture();

    // Bring the candidate's existing workload back under threshold.
    Task::where('reference', 'TSK-2026-0002')->update(['complexity_weight' => 2]);

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['task' => $task])
        ->call('selectCandidate', $candidate->id)
        ->assertSet('showWarning', false)
        ->call('confirmAssign');

    expect($task->fresh()->assigned_to)->toBe($candidate->id);

    $log = AuditLog::query()->where('auditable_id', $task->id)->first();
    expect($log)->not->toBeNull()->and($log->action)->toBe('task_assigned');
});

it('denies employees from opening the assignment modal', function () {
    ['candidate' => $candidate, 'task' => $task] = assignModalFixture();

    Livewire::actingAs($candidate)
        ->test(AssignTaskModal::class, ['task' => $task])
        ->assertForbidden();
});
