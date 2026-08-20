<?php

use App\Models\Account;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function employeeRole(): Role
{
    return Role::query()->firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);
}

it('forbids an employee from updating a task assigned to someone else', function () {
    $roleId = employeeRole()->id;

    $owner = User::factory()->create(['role_id' => $roleId]);
    $other = User::factory()->create(['role_id' => $roleId]);
    $account = Account::factory()->create();

    $task = Task::factory()->create([
        'account_id' => $account->id,
        'assigned_to' => $owner->id,
        'created_by' => $owner->id,
    ]);

    $this->actingAs($other)
        ->patch(route('my.tasks.start', $task))
        ->assertForbidden();

    expect($task->fresh()->status->value)->toBe('pending');
});

it('allows the assigned employee to start their own task', function () {
    $roleId = employeeRole()->id;

    $owner = User::factory()->create(['role_id' => $roleId]);
    $account = Account::factory()->create();

    $task = Task::factory()->create([
        'account_id' => $account->id,
        'assigned_to' => $owner->id,
        'created_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('my.tasks.start', $task))
        ->assertRedirect();

    $task->refresh();

    expect($task->status->value)->toBe('in_progress');
    expect($task->started_at)->not->toBeNull();
});
