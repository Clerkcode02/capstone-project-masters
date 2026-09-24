<?php

use App\Domain\Tasks\Exceptions\OverAllocationWarning;
use App\Domain\Tasks\Services\TaskAssignmentService;
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

function taskAssignmentAccount(): Account
{
    return Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);
}

function taskAssignmentUser(string $roleName, string $email): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);
    $designation = Designation::firstOrCreate(
        ['name' => 'Reports Analyst'],
        ['utilization_target' => '0.800']
    );

    return User::create([
        'first_name' => 'Test',
        'last_name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

function taskAssignmentSeedThreshold(int $threshold = 12): void
{
    Setting::create(['key' => 'workload_threshold', 'value' => (string) $threshold, 'type' => 'int', 'group' => 'workload', 'label' => 'Workload threshold']);
}

it('creates the task immediately when the assignment stays within the workload threshold', function () {
    taskAssignmentSeedThreshold();
    $account = taskAssignmentAccount();
    $manager = taskAssignmentUser('manager', 'manager@example.com');
    $employee = taskAssignmentUser('employee', 'employee@example.com');
    $account->users()->attach($employee->id);

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['account' => $account])
        ->set('form.title', 'Weekly toning report')
        ->set('form.complexity_tier', 'medium')
        ->set('form.standard_hours', 8)
        ->set('form.assigned_to', $employee->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('confirmingOverAllocation', false);

    $task = Task::sole();
    expect($task->assigned_to)->toBe($employee->id);
    expect($task->complexity_weight)->toBe(3); // default medium weight
    expect($task->status->value)->toBe('pending');

    expect(AuditLog::where('action', 'task_assigned')->count())->toBe(1);
    expect(AuditLog::where('action', 'over_allocation_override')->count())->toBe(0);
});

it('warns instead of creating the task when the assignment would exceed the workload threshold', function () {
    taskAssignmentSeedThreshold(threshold: 4);
    $account = taskAssignmentAccount();
    $manager = taskAssignmentUser('manager', 'manager@example.com');
    $employee = taskAssignmentUser('employee', 'employee@example.com');
    $account->users()->attach($employee->id);

    // Employee already has a medium (weight 3) in-progress task; threshold is 4.
    Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Existing task',
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'created_by' => $manager->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => '8.00',
        'status' => 'in_progress',
    ]);

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['account' => $account])
        ->set('form.title', 'Another report')
        ->set('form.complexity_tier', 'medium')
        ->set('form.standard_hours', 8)
        ->set('form.assigned_to', $employee->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('confirmingOverAllocation', true)
        ->assertSet('evaluationCurrentScore', 3)
        ->assertSet('evaluationProspectiveScore', 6)
        ->assertSet('evaluationThreshold', 4);

    expect(Task::count())->toBe(1); // only the pre-existing one — nothing silently created
    expect(AuditLog::where('action', 'over_allocation_override')->count())->toBe(0);
});

it('creates the task and logs an override when a manager assigns anyway', function () {
    taskAssignmentSeedThreshold(threshold: 4);
    $account = taskAssignmentAccount();
    $manager = taskAssignmentUser('manager', 'manager@example.com');
    $employee = taskAssignmentUser('employee', 'employee@example.com');
    $account->users()->attach($employee->id);

    Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Existing task',
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'created_by' => $manager->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => '8.00',
        'status' => 'in_progress',
    ]);

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['account' => $account])
        ->set('form.title', 'Another report')
        ->set('form.complexity_tier', 'medium')
        ->set('form.standard_hours', 8)
        ->set('form.assigned_to', $employee->id)
        ->call('save')
        ->assertSet('confirmingOverAllocation', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('confirmingOverAllocation', false);

    expect(Task::count())->toBe(2);
    expect(AuditLog::where('action', 'task_assigned')->count())->toBe(1);
    expect(AuditLog::where('action', 'over_allocation_override')->count())->toBe(1);
});

it('resets a shown warning when the candidate changes', function () {
    taskAssignmentSeedThreshold(threshold: 4);
    $account = taskAssignmentAccount();
    $manager = taskAssignmentUser('manager', 'manager@example.com');
    $overloaded = taskAssignmentUser('employee', 'overloaded@example.com');
    $spare = taskAssignmentUser('employee', 'spare@example.com');
    $account->users()->attach([$overloaded->id, $spare->id]);

    Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Existing task',
        'account_id' => $account->id,
        'assigned_to' => $overloaded->id,
        'created_by' => $manager->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => '8.00',
        'status' => 'in_progress',
    ]);

    Livewire::actingAs($manager)
        ->test(AssignTaskModal::class, ['account' => $account])
        ->set('form.title', 'Another report')
        ->set('form.complexity_tier', 'medium')
        ->set('form.standard_hours', 8)
        ->set('form.assigned_to', $overloaded->id)
        ->call('save')
        ->assertSet('confirmingOverAllocation', true)
        ->set('form.assigned_to', $spare->id)
        ->assertSet('confirmingOverAllocation', false);
});

it('forbids an employee from creating or assigning a task', function () {
    $account = taskAssignmentAccount();
    $employee = taskAssignmentUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(AssignTaskModal::class, ['account' => $account])
        ->assertStatus(403);
});

it('throws OverAllocationWarning from the service when not confirmed, and never touches assigned_to elsewhere', function () {
    taskAssignmentSeedThreshold(threshold: 2);
    $account = taskAssignmentAccount();
    $manager = taskAssignmentUser('manager', 'manager@example.com');
    $employee = taskAssignmentUser('employee', 'employee@example.com');

    $service = app(TaskAssignmentService::class);

    expect(fn () => $service->assign([
        'title' => 'Large report',
        'description' => null,
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'complexity_tier' => 'large',
        'standard_hours' => 18,
        'due_date' => null,
    ], $manager))->toThrow(OverAllocationWarning::class);

    expect(Task::count())->toBe(0);
});
